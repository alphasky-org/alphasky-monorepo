<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\Theme\Facades\Theme;
use Illuminate\Support\Facades\File;
use Alphasky\Theme\Events\RenderingThemeOptionSettings;
use Alphasky\Theme\Facades\ThemeOption;

final class ThemeStructure
{
    /** Only static public markup identifiers, never Blade expressions, text or settings values. */
    public static function inventory(): array
    {
        $name = Theme::getThemeName();
        $root = realpath(theme_path($name));
        $views = [];
        $bindings = [];
        if (!$root || !preg_match('/^[a-z0-9_-]+$/iD', $name)) return [];
        foreach (File::allFiles($root) as $file) {
            $path = $file->getRelativePathname();
            $widget = preg_match('~^widgets/[\w-]+/templates/frontend\.blade\.php$~D', $path);
            if ((!$widget && !preg_match('~^(partials|views|layouts)/(?:[\w-]+/)*[\w-]+\.blade\.php$~D', $path))
                || !str_starts_with((string) $file->getRealPath(), $root.DIRECTORY_SEPARATOR) || $file->getSize() > 200000) continue;
            $source = preg_replace('~\{\{--.*?--\}\}|<!--.*?-->~s', '', $file->getContents());
            $bindings[$path] = self::bindings($source);
            if ($widget) continue;
            $markup = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|<\?.*?\?>/s', '', $source);
            preg_match_all('~<(header|footer|nav|section|article|div|main|ul|h[1-6]|p|aside|button|summary|details|a)\b[^<>]*?\bclass=["\']([^"\']*)["\']~i', $markup, $matches, PREG_SET_ORDER);
            $selectors = [];
            foreach ($matches as $m) {
                foreach (preg_split('/\s+/', trim($m[2])) as $class) {
                    if (preg_match('/^[a-zA-Z][\w-]{0,60}$/D', $class)) $selectors['.'.$class] = true;
                }
            }
            $views[$path] = array_slice(array_keys($selectors), 0, 70);

        }
        $content = [];
        if (class_exists(\Alphasky\Page\Models\Page::class) && \Illuminate\Support\Facades\Schema::hasTable('pages')) {
            foreach (\Alphasky\Page\Models\Page::query()->wherePublished()->limit(20)->pluck('content') as $html) {
                preg_match_all('~<(section|article|p|h[1-6]|details|summary|img|div|ul|li)\b([^<>]*)>~i', substr((string)$html, 0, 65536), $tags, PREG_SET_ORDER);
                foreach ($tags as $tag) {
                    $selector = strtolower($tag[1]);
                    if (preg_match('/\bdata-source-page\s*=/i', $tag[2])) $selector .= '[data-source-page]';
                    $content[$selector] = true;
                }
            }
        }
        $footer = $bindings['partials/footer.blade.php']['options'] ?? [];
        $contact = [];
        foreach (['address', 'phone'] as $field) {
            $contact[$field] = ['configured' => filled(theme_option($field)), 'bound' => in_array($field, $footer, true)];
        }
        return ['theme'=>$name,'views'=>$views,'content_selectors'=>array_keys($content),
            'footer_contact'=>$contact, 'page_layouts'=>self::pageLayouts(), 'blade_schema'=>1, 'bindings'=>array_filter($bindings), 'theme_options'=>self::options()];
    }

    /** Public page identities and current native template selections. */
    private static function pageLayouts(): array
    {
        if (!class_exists(\Alphasky\Page\Models\Page::class) || !\Illuminate\Support\Facades\Schema::hasTable('pages')) return [];
        $home = (int) \Alphasky\Base\Facades\BaseHelper::getHomepageId();
        $templates = \Alphasky\Page\Supports\Template::getPageTemplates();
        $pages = \Alphasky\Page\Models\Page::query()->wherePublished()
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$home])->orderBy('id')
            ->limit(200)->get(['id','name','template']);
        $slugs = \Alphasky\Slug\Models\Slug::where('reference_type',\Alphasky\Page\Models\Page::class)
            ->whereIn('reference_id',$pages->modelKeys())->pluck('key','reference_id');
        return ['templates'=>$templates,'pages'=>$pages->map(static fn($page)=>[
            'id'=>(int)$page->id,'title'=>(string)$page->name,'slug'=>(string)($slugs[$page->id]??''),
            'homepage'=>(int)$page->id===$home,'template'=>$page->template?:'default',
        ])->all()];
    }

    /** Describe literal public bindings; never evaluate the template or send its source. */
    public static function bindings(string $source): array
    {
        $patterns = [
            'options' => 'theme_option',
            'partials' => 'Theme::partial',
            'layouts' => 'Theme::layout',
            'sidebars' => 'dynamic_sidebar',
            'menus' => 'Menu::renderMenuLocation',
            'plugins' => 'is_plugin_active',
            'routes' => 'route',
            'filters' => 'apply_filters',
            'theme_slots' => 'Theme::(?:get|set)',
            'includes' => '@include',
        ];
        $result = [];
        foreach ($patterns as $group => $call) {
            preg_match_all('~(?<![\w])'.$call.'\s*\(\s*(["\x27])([a-zA-Z0-9_./-]{1,120})\1\s*[,)]~', $source, $matches);
            if ($matches[2]) $result[$group] = array_values(array_unique($matches[2]));
        }
        $references = [
            'menu_views' => '~["\x27]view["\x27]\s*=>\s*["\x27]([\w./-]+)["\x27]~',
            'widget_fields' => '~\$config\[["\x27]([\w-]+)["\x27]\]~',
            'shortcode_attributes' => '~\$shortcode->([a-zA-Z]\w*)\b(?!\s*\()~',
            'included_theme_views' => '~Theme::getThemeNamespace\(\)\s*\.\s*["\x27]::([\w.-]+)["\x27]~',
        ];
        foreach ($references as $group => $pattern) {
            preg_match_all($pattern, $source, $matches);
            if ($matches[1]) $result[$group] = array_values(array_unique($matches[1]));
        }
        preg_match_all('~\b(?:apply_filters|do_action)\s*\(\s*([A-Z][A-Z0-9_]+)\s*[,)]~', $source, $hooks);
        if ($hooks[1]) $result['hooks'] = array_values(array_unique($hooks[1]));
        preg_match_all('~\b(Theme|Menu|RvMedia|SeoHelper|BaseHelper|Shortcode)::([a-zA-Z]\w{0,63})\s*\(~', $source, $calls, PREG_SET_ORDER);
        foreach ($calls as $call) $result['helpers'][] = $call[1].'::'.$call[2];
        if (isset($result['helpers'])) $result['helpers'] = array_values(array_unique($result['helpers']));
        return $result;
    }

    private static function options(): array
    {
        RenderingThemeOptionSettings::dispatch();
        do_action(RENDERING_THEME_OPTIONS_PAGE);
        $options = [];
        foreach (ThemeOption::getFields()['theme'] ?? [] as $id => $field) {
            $key = $field['attributes']['name'] ?? $id;
            if (!is_string($key) || !preg_match('/^[\w.-]{1,128}$/D', $key)) continue;
            $options[$key] = [
                'type' => $field['type'] ?? 'text',
                'section' => $field['section_id'] ?? '',
            ];
        }
        return $options;
    }
}
