<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\Page\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Site-owned inventory and append-only publication; never evaluates model-supplied code. */
final class DesignUiBlocks
{
    private static bool $registryBooted = false;

    private function bootRegistry(): void
    {
        if (self::$registryBooted || !app()->runningInConsole()) return;
        // Themes register UI Blocks on RouteMatched. CLI workers have no matched route yet.
        $request = \Illuminate\Http\Request::create(rtrim(config('app.url'), '/').'/', 'GET');
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        app('events')->dispatch(new \Illuminate\Routing\Events\RouteMatched($route, $request));
        self::$registryBooted = true;
    }

    public function inventory(): array
    {
        $this->bootRegistry();
        $blocks = [];
        foreach (array_slice(shortcode()->getAll(), 0, 200, true) as $key => $block) {
            $blocks[] = ['name' => $key, 'title' => mb_substr(strip_tags((string) ($block['name'] ?? $key)), 0, 200),
                'description' => mb_substr(strip_tags((string) ($block['description'] ?? '')), 0, 600), 'schema' => $this->schema($key, $block)];
        }
        $pages = [];
        foreach (Page::query()->where('status', 'published')->orderBy('id')->limit(150)->get() as $page) {
            $content = (string) $page->getRawOriginal('content');
            preg_match_all('/\[([a-z][a-z0-9_-]*)\b/', $content, $matches);
            $pages[] = ['id' => (int) $page->id, 'name' => mb_substr($page->name, 0, 200),
                'homepage' => (int) theme_option('homepage_id') === (int) $page->id,
                'content_sha256' => hash('sha256', $content), 'blocks' => array_values(array_unique($matches[1]))];
        }
        return ['version' => 1, 'blocks' => $blocks, 'pages' => $pages];
    }

    private function schema(string $key, array $block): ?array
    {
        if ($key === 'google-map') return ['kind' => 'map', 'content' => 'address', 'attributes' => ['width', 'height']];
        if ($key === 'youtube-video') return ['kind' => 'video', 'content' => 'https_youtube_url', 'attributes' => ['width', 'height']];
        if ($key === 'blog-posts') return ['kind' => 'public_data', 'attributes' => ['paginate']];
        $schema = $block['design_schema'] ?? null;
        return is_array($schema) && ($schema['kind'] ?? '') === 'public_data' ? ['kind' => 'public_data', 'attributes' => ['paginate']] : null;
    }

    public function block(array $input): string
    {
        $this->bootRegistry();
        $name = $input['name'] ?? '';
        if (!is_string($name) || !preg_match('/^[a-z][a-z0-9_-]{0,120}$/D', $name)) throw new RuntimeException('invalid_ui_block');
        $registered = shortcode()->getAll()[$name] ?? null;
        $schema = $registered ? $this->schema($name, $registered) : null;
        if (!$schema) throw new RuntimeException('ui_block_not_supported');
        $attrs = $input['attributes'] ?? [];
        if (!is_array($attrs) || array_diff(array_keys($attrs), $schema['attributes'])) throw new RuntimeException('invalid_ui_attributes');
        foreach ($attrs as $key => $value) {
            if (!is_scalar($value)) throw new RuntimeException('invalid_ui_attribute');
            if ($key === 'width' && $value === '100%') continue;
            $max = $key === 'paginate' ? 100 : 2000;
            if (!preg_match('/^[1-9][0-9]{0,3}$/D', (string) $value) || (int) $value > $max) throw new RuntimeException('invalid_ui_dimension');
        }
        $content = $input['content'] ?? '';
        if (!is_string($content) || mb_strlen($content) > 1000 || preg_match('/[<>\[\]\x00-\x1f]/u', $content)) throw new RuntimeException('unsafe_ui_content');
        if ($schema['kind'] === 'map' && trim($content) === '') throw new RuntimeException('map_address_required');
        if ($schema['kind'] === 'video') {
            $parts = parse_url($content);
            if (!$parts || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['port']) || !in_array(strtolower($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'youtu.be'], true)
                || !preg_match('~(?:youtu\.be/|[?&]v=|/embed/)([a-zA-Z0-9_-]{11})(?:[?&#]|$)~', $content)) throw new RuntimeException('invalid_youtube_url');
        }
        if ($schema['kind'] === 'public_data' && $content !== '') throw new RuntimeException('unexpected_ui_content');
        return shortcode()->generateShortcode($name, $attrs, $content);
    }

    public function apply(string $operation, array $plan): array
    {
        if (!preg_match('/^[a-f0-9-]{36}$/D', $operation) || !is_int($plan['page_id'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $plan['content_sha256'] ?? '')
            || !is_array($plan['blocks'] ?? null) || count($plan['blocks']) < 1 || count($plan['blocks']) > 8) throw new RuntimeException('invalid_ui_plan');
        $fragment = '';
        foreach ($plan['blocks'] as $index => $block) {
            $fragment .= "\n".'<div data-design-ui="'.$operation.'-'.$index.'">'.$this->block($block).'</div>';
        }
        $root = storage_path('app/private/website-design/page-backups');
        if (!is_dir($root) && !mkdir($root, 0700, true)) throw new RuntimeException('page_backup_unavailable');
        $result = DB::transaction(function () use ($operation, $plan, $fragment, $root) {
            $row = DB::table('pages')->where('id', $plan['page_id'])->lockForUpdate()->first();
            if (!$row || $row->status !== 'published') throw new RuntimeException('page_unavailable');
            $content = (string) $row->content;
            if (!str_contains($content, $fragment)) {
                if (!hash_equals($plan['content_sha256'], hash('sha256', $content))) throw new RuntimeException('page_changed_since_plan');
                $backup = $root.'/'.$operation.'.json';
                if (!is_file($backup)) {
                    if (file_put_contents($backup, json_encode(['page_id' => $row->id, 'content' => $content], JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('page_backup_failed');
                    chmod($backup, 0600);
                }
                DB::table('pages')->where('id', $row->id)->update(['content' => $content.$fragment, 'updated_at' => now()]);
            }
            $page = Page::query()->findOrFail($row->id);
            $url = (int) theme_option('homepage_id') === (int) $page->id ? rtrim(config('app.url'), '/').'/' : (string) $page->url;
            if (parse_url($url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST) || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER)) throw new RuntimeException('invalid_page_url');
            return ['url' => $url, 'operation' => $operation, 'blocks' => $plan['blocks']];
        });
        return $result;
    }

    public function verify(array $publication): void
    {
        $url = $publication['url'];
        if (parse_url($url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST) || parse_url($url, PHP_URL_SCHEME) !== 'https') throw new RuntimeException('invalid_page_url');
        $response = Http::connectTimeout(10)->timeout(45)->withoutRedirecting()->withHeaders(['Cache-Control' => 'no-cache'])->get($url, ['runure_ui_revision' => $publication['operation']]);
        if (!$response->successful()) throw new RuntimeException('ui_page_verification_failed');
        $old = libxml_use_internal_errors(true);
        try {
            $doc = new \DOMDocument(); $doc->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NONET); $xpath = new \DOMXPath($doc);
            foreach ($publication['blocks'] as $index => $block) {
                $nodes = $xpath->query('//*[@data-design-ui="'.$publication['operation'].'-'.$index.'"]');
                if ($nodes->length !== 1) throw new RuntimeException('ui_block_not_rendered');
                $node = $nodes->item(0); $html = $doc->saveHTML($node);
                if (str_contains($html, '['.$block['name']) || !$node->hasChildNodes()) throw new RuntimeException('ui_block_not_rendered');
                if (in_array($block['name'], ['google-map', 'youtube-video'], true)) {
                    $frames = $node->getElementsByTagName('iframe'); $matched = false;
                    foreach ($frames as $frame) {
                        $src = $frame->getAttribute('src'); $parts = parse_url($src); parse_str($parts['query'] ?? '', $query);
                        if ($block['name'] === 'google-map' && in_array($parts['host'] ?? '', ['maps.google.com','www.google.com'], true) && ($query['q'] ?? null) === $block['content']) $matched = true;
                        if ($block['name'] === 'youtube-video' && in_array($parts['host'] ?? '', ['www.youtube.com','youtube.com','www.youtube-nocookie.com'], true)
                            && preg_match('~(?:youtu\.be/|[?&]v=|/embed/)([a-zA-Z0-9_-]{11})~', $block['content'], $video) && str_contains($parts['path'] ?? '', '/embed/'.$video[1])) $matched = true;
                    }
                    if (!$matched) throw new RuntimeException('ui_media_verification_failed');
                } elseif (!$node->getElementsByTagName('*')->length && trim($node->textContent) === '') throw new RuntimeException('ui_data_verification_failed');
            }
        } finally { libxml_clear_errors(); libxml_use_internal_errors($old); }
    }
}
