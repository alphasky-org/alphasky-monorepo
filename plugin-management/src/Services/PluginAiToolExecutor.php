<?php

namespace Alphasky\PluginManagement\Services;

use Alphasky\Base\Facades\BaseHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;

class PluginAiToolExecutor
{
    protected int $maxFileBytes = 524288;

    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected PluginService $pluginService,
    ) {
    }

    public function execute(array $request): array
    {
        $tool = $this->normalizeTool((string) Arr::get($request, 'tool', Arr::get($request, 'action', '')));
        $params = is_array($request['params'] ?? null) ? $request['params'] : $request;

        return match ($tool) {
            'list_plugins' => $this->listPlugins($params),
            'install_plugin' => $this->installPlugin($params),
            'list_plugin_files' => $this->listPluginFiles($params),
            'read_plugin_file' => $this->readPluginFile($params),
            'search_plugin_files' => $this->searchPluginFiles($params),
            'show_plugin_function' => $this->showPluginFunction($params),
            'generate_ai_image' => $this->generateAiImage($params),
            default => $this->error('Unsupported AI plugin tool. Supported tools: list_plugins, install_plugin, list_plugin_files, read_plugin_file, search_plugin_files, show_plugin_function, generate_ai_image.'),
        };
    }

    protected function listPlugins(array $params): array
    {
        $localPlugins = [];
        $activePlugins = get_active_plugins();

        foreach (BaseHelper::scanFolder(plugin_path()) as $plugin) {
            $path = plugin_path($plugin);

            if (! File::isDirectory($path) || ! File::exists($path . '/plugin.json')) {
                continue;
            }

            $manifest = BaseHelper::getFileData($path . '/plugin.json') ?: [];
            $localPlugins[] = [
                'name' => $plugin,
                'id' => Arr::get($manifest, 'id'),
                'title' => Arr::get($manifest, 'name', Str::headline($plugin)),
                'version' => Arr::get($manifest, 'version'),
                'active' => in_array($plugin, $activePlugins, true),
                'path' => 'platform/plugins/' . $plugin,
            ];
        }

        $marketplacePlugins = [];
        $includeServer = filter_var(Arr::get($params, 'include_server', true), FILTER_VALIDATE_BOOLEAN);

        if ($includeServer) {
            try {
                $response = $this->marketplaceService->callApi('get', '/products', Arr::only($params, ['keyword', 'page', 'per_page', 'category_id']));
                $marketplacePlugins = array_map(fn (array $plugin): array => [
                    'id' => $plugin['id'] ?? null,
                    'name' => Str::afterLast((string) ($plugin['package_name'] ?? $plugin['name'] ?? ''), '/'),
                    'title' => $plugin['name'] ?? null,
                    'package_name' => $plugin['package_name'] ?? null,
                    'version' => $plugin['version'] ?? null,
                    'minimum_core_version' => $plugin['minimum_core_version'] ?? null,
                ], (array) $response->json('data', []));
            } catch (\Throwable $exception) {
                $marketplacePlugins = [
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $this->success([
            'installed' => $localPlugins,
            'active' => array_values($activePlugins),
            'server' => $marketplacePlugins,
        ]);
    }

    protected function installPlugin(array $params): array
    {
        $plugin = $this->normalizePluginName((string) Arr::get($params, 'plugin', Arr::get($params, 'name', '')));
        $id = trim((string) Arr::get($params, 'id', ''));

        if ($plugin === '' && $id === '') {
            return $this->error('Plugin name or marketplace id is required.');
        }

        try {
            if ($id === '') {
                $response = $this->marketplaceService->callApi('post', '/products/check-update', [
                    'products' => [$plugin => '0.0.0'],
                ]);
                $id = (string) $response->json('data.0.id', '');
            }

            if ($id === '') {
                return $this->error("Plugin {$plugin} was not found on the marketplace.");
            }

            if ($plugin === '') {
                $detail = $this->marketplaceService->callApi('get', '/products/' . $id)->json('data', []);
                $plugin = $this->normalizePluginName(Str::afterLast((string) Arr::get($detail, 'package_name', Arr::get($detail, 'name', '')), '/'));
            }

            $this->marketplaceService->beginInstall($id, $plugin, $this->pluginService);

            return $this->success([
                'plugin' => $plugin,
                'id' => $id,
                'installed' => true,
                'path' => 'platform/plugins/' . $plugin,
            ], "Installed plugin {$plugin} successfully.");
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage());
        }
    }

    protected function listPluginFiles(array $params): array
    {
        $root = $this->pluginRootFromParams($params);
        $maxDepth = max(1, min(20, (int) Arr::get($params, 'depth', 20)));
        $files = [];
        $folders = [];

        foreach ($this->collectDirectories($root) as $directory) {
            $relative = $this->relativePath($root, $directory);
            $depth = substr_count($relative, '/');

            if ($depth >= $maxDepth) {
                continue;
            }

            $folders[] = [
                'type' => 'directory',
                'path' => $relative,
            ];
        }

        foreach (File::allFiles($root) as $file) {
            $relative = $this->relativePath($root, $file->getPathname());
            $depth = substr_count($relative, '/');

            if ($depth >= $maxDepth) {
                continue;
            }

            $files[] = [
                'type' => 'file',
                'path' => $relative,
                'size' => $file->getSize(),
                'human_size' => $this->humanSize($file->getSize()),
            ];
        }

        usort($folders, fn (array $first, array $second): int => strcmp($first['path'], $second['path']));
        usort($files, fn (array $first, array $second): int => strcmp($first['path'], $second['path']));

        return $this->success([
            'plugin' => basename($root),
            'root' => 'platform/plugins/' . basename($root),
            'folders' => $folders,
            'files' => $files,
            'entries' => array_values(array_merge($folders, $files)),
        ]);
    }

    protected function readPluginFile(array $params): array
    {
        $root = $this->pluginRootFromParams($params);
        $path = $this->resolveInsideRoot($root, (string) Arr::get($params, 'file', Arr::get($params, 'path', '')));
        $this->assertReadableTextFile($path);

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $total = count($lines ?: []);
        $mode = strtolower((string) Arr::get($params, 'mode', 'range'));
        $limit = max(1, min(500, (int) Arr::get($params, 'lines', 100)));

        if ($mode === 'first') {
            $start = 1;
            $end = min($total, $limit);
        } elseif ($mode === 'last') {
            $start = max(1, $total - $limit + 1);
            $end = $total;
        } else {
            $start = max(1, (int) Arr::get($params, 'start', 1));
            $end = min($total, (int) Arr::get($params, 'end', $start + $limit - 1));
        }

        $content = [];
        for ($line = $start; $line <= $end; $line++) {
            $content[] = [
                'line' => $line,
                'text' => $lines[$line - 1] ?? '',
            ];
        }

        return $this->success([
            'file' => $this->relativePath($root, $path),
            'total_lines' => $total,
            'start' => $start,
            'end' => $end,
            'content' => $content,
        ]);
    }

    protected function searchPluginFiles(array $params): array
    {
        $root = $this->pluginRootFromParams($params);
        $query = (string) Arr::get($params, 'query', '');
        $caseSensitive = filter_var(Arr::get($params, 'case_sensitive', false), FILTER_VALIDATE_BOOLEAN);
        $maxMatches = max(1, min(500, (int) Arr::get($params, 'max_matches', 100)));

        if ($query === '') {
            return $this->error('Search query is required.');
        }

        $matches = [];

        foreach (File::allFiles($root) as $file) {
            if (count($matches) >= $maxMatches || ! $this->isLikelyTextFile($file)) {
                continue;
            }

            foreach (file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [] as $index => $line) {
                $found = $caseSensitive ? str_contains($line, $query) : str_contains(Str::lower($line), Str::lower($query));

                if (! $found) {
                    continue;
                }

                $matches[] = [
                    'file' => $this->relativePath($root, $file->getPathname()),
                    'line' => $index + 1,
                    'text' => $line,
                ];

                if (count($matches) >= $maxMatches) {
                    break 2;
                }
            }
        }

        return $this->success([
            'query' => $query,
            'matches' => $matches,
        ]);
    }

    protected function showPluginFunction(array $params): array
    {
        $root = $this->pluginRootFromParams($params);
        $path = $this->resolveInsideRoot($root, (string) Arr::get($params, 'file', Arr::get($params, 'path', '')));
        $function = (string) Arr::get($params, 'function', Arr::get($params, 'name', ''));
        $this->assertReadableTextFile($path);

        if ($function === '') {
            return $this->error('Function name is required.');
        }

        $tokens = token_get_all(File::get($path));
        $line = null;

        for ($index = 0, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];
            if (! is_array($token) || $token[0] !== T_FUNCTION) {
                continue;
            }

            $nameIndex = $this->nextMeaningfulTokenIndex($tokens, $index + 1);
            if ($nameIndex === null || ! is_array($tokens[$nameIndex]) || $tokens[$nameIndex][0] !== T_STRING) {
                continue;
            }

            if ($tokens[$nameIndex][1] === $function) {
                $line = $token[2];
                break;
            }
        }

        if ($line === null) {
            return $this->error("Function {$function} was not found.");
        }

        return $this->readFunctionByLine($root, $path, $line);
    }

    protected function generateAiImage(array $params): array
    {
        $plugin = $this->normalizePluginName((string) Arr::get($params, 'plugin', Arr::get($params, 'name', '')));
        $target = (string) Arr::get($params, 'file', Arr::get($params, 'path', 'screenshot.png'));

        if ($plugin === '') {
            return $this->error('Plugin name is required.');
        }

        return $this->success([
            'plugin' => $plugin,
            'file' => trim(str_replace('\\', '/', $target), '/'),
            'execution' => 'server',
            'message' => 'AI image generation must run on the Alphasky server. The generated image should be sent back as a plugin_asset payload for this client to write locally.',
        ], 'AI image generation is a server-side tool.');
    }

    public function writePluginAsset(string $plugin, string $relativePath, string $content, string $encoding = 'base64'): array
    {
        $plugin = $this->normalizePluginName($plugin);
        $root = $this->pluginRoot($plugin);
        $path = $this->resolveInsideRoot($root, $relativePath);
        $binary = $encoding === 'base64' ? base64_decode($content, true) : $content;

        if (! is_string($binary) || $binary === '') {
            return $this->error('Invalid plugin asset content.');
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $binary);

        return $this->success([
            'plugin' => $plugin,
            'file' => $this->relativePath($root, $path),
            'path' => 'platform/plugins/' . $plugin . '/' . $this->relativePath($root, $path),
            'size' => strlen($binary),
            'human_size' => $this->humanSize(strlen($binary)),
        ], 'Plugin asset written successfully.');
    }

    protected function readFunctionByLine(string $root, string $path, int $functionLine): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $startIndex = max(0, $functionLine - 1);
        $braceDepth = 0;
        $seenBody = false;
        $endIndex = min(count($lines) - 1, $startIndex + 500);

        for ($index = $startIndex; $index < count($lines); $index++) {
            $line = $lines[$index];
            $braceDepth += substr_count($line, '{');
            $braceDepth -= substr_count($line, '}');

            if (str_contains($line, '{')) {
                $seenBody = true;
            }

            if ($seenBody && $braceDepth <= 0) {
                $endIndex = $index;
                break;
            }
        }

        $content = [];
        for ($line = $startIndex + 1; $line <= $endIndex + 1; $line++) {
            $content[] = [
                'line' => $line,
                'text' => $lines[$line - 1] ?? '',
            ];
        }

        return $this->success([
            'file' => $this->relativePath($root, $path),
            'function_line' => $functionLine,
            'content' => $content,
        ]);
    }

    protected function nextMeaningfulTokenIndex(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($token === '&') {
                continue;
            }

            return $index;
        }

        return null;
    }

    protected function pluginRootFromParams(array $params): string
    {
        return $this->pluginRoot((string) Arr::get($params, 'plugin', Arr::get($params, 'name', '')));
    }

    protected function collectDirectories(string $root): array
    {
        $directories = [];

        foreach (File::directories($root) as $directory) {
            $directories[] = $directory;
            array_push($directories, ...$this->collectDirectories($directory));
        }

        return $directories;
    }

    protected function pluginRoot(string $plugin): string
    {
        $plugin = $this->normalizePluginName($plugin);

        if ($plugin === '') {
            throw new InvalidArgumentException('Plugin name is required.');
        }

        $path = plugin_path($plugin);
        if (! File::isDirectory($path)) {
            throw new InvalidArgumentException("Plugin {$plugin} does not exist locally.");
        }

        return realpath($path) ?: $path;
    }

    protected function resolveInsideRoot(string $root, string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            throw new InvalidArgumentException('A safe relative file path is required.');
        }

        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $real = realpath($path);

        if ($real !== false && ! Str::startsWith($real, $root . DIRECTORY_SEPARATOR) && $real !== $root) {
            throw new InvalidArgumentException('File path is outside the plugin directory.');
        }

        if ($real !== false) {
            return $real;
        }

        $directory = realpath(dirname($path));
        if ($directory === false || ($directory !== $root && ! Str::startsWith($directory, $root . DIRECTORY_SEPARATOR))) {
            throw new InvalidArgumentException('File path is outside the plugin directory.');
        }

        return $path;
    }

    protected function assertReadableTextFile(string $path): void
    {
        if (! File::isFile($path)) {
            throw new InvalidArgumentException('File does not exist.');
        }

        if (File::size($path) > $this->maxFileBytes) {
            throw new InvalidArgumentException('File is too large to read through the AI helper.');
        }

        if (! $this->isLikelyTextPath($path)) {
            throw new InvalidArgumentException('Only text files can be read through the AI helper.');
        }
    }

    protected function isLikelyTextFile(SplFileInfo $file): bool
    {
        return $file->getSize() <= $this->maxFileBytes && $this->isLikelyTextPath($file->getPathname());
    }

    protected function isLikelyTextPath(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, [
            'php', 'blade.php', 'js', 'ts', 'vue', 'css', 'scss', 'json', 'md', 'txt', 'xml', 'yml', 'yaml', 'env', 'stub', 'html', 'htm', 'sql',
        ], true) || ! str_contains(basename($path), '.');
    }

    protected function relativePath(string $root, string $path): string
    {
        return str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));
    }

    protected function normalizeTool(string $tool): string
    {
        return Str::snake(str_replace(['-', ' '], '_', trim($tool)));
    }

    protected function normalizePluginName(string $plugin): string
    {
        $plugin = Str::afterLast(trim($plugin), '/');

        if ($plugin !== '' && ! preg_match('/^[a-z0-9_-]+$/', $plugin)) {
            throw new InvalidArgumentException('Invalid plugin name.');
        }

        return $plugin;
    }

    protected function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return round($bytes, 2) . ' ' . $unit;
            }

            $bytes = (int) floor($bytes / 1024);
        }

        return $bytes . ' B';
    }

    protected function success(array $data, string $message = 'OK'): array
    {
        return [
            'error' => false,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function error(string $message): array
    {
        return [
            'error' => true,
            'message' => $message,
        ];
    }
}
