<?php

namespace Alphasky\PluginManagement\Http\Controllers;

use Alphasky\Alphaskyplugin\Models\Alphaskyplugin;
use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Supports\Breadcrumb;
use Alphasky\PluginManagement\Services\MarketplaceService;
use Alphasky\PluginManagement\Services\PluginService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Throwable;

class MarketplaceController extends BaseController
{
    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected PluginService $pluginService,
    ) {
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('packages/plugin-management::plugin.plugins'), route('plugins.index'))
            ->add(trans('packages/plugin-management::plugin.plugins_add_new'), route('plugins.new'));
    }

    public function index(): View
    {
       
        $this->pageTitle(trans('packages/plugin-management::plugin.plugins_add_new'));

        $marketplaceAsset = 'vendor/core/packages/plugin-management/js/marketplace.js';

        Assets::usingVueJS()
            ->addScriptsDirectly($marketplaceAsset . '?v=' . filemtime(public_path($marketplaceAsset)));

        return view('packages/plugin-management::marketplace');
    }

    public function list(Request $request): array|BaseHttpResponse
    {
        $request->merge([
            'type' => 'plugin',
            'alphasky_key' => trim((string) $request->header('X-Alphasky-Key', '')),
        ]);

        try {
            $response = $this->marketplaceService->callApi('get', '/products', $request->input());
        } catch (Throwable $exception) {
            $statusCode = $exception->getCode();

            return $this
                ->httpResponse()
                ->setError()
                ->setCode($statusCode >= 400 && $statusCode < 600 ? $statusCode : 500)
                ->setMessage($exception->getMessage());
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
        } else {
            $data = $response->json();
        }

        if (isset($data['error']) && $data['error']) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($data['message']);
        }

        if (empty($data['data'])) {
            return $data;
        }

        $coreVersion = get_core_version();

        foreach ($data['data'] as $key => $item) {
            $data['data'][$key]['version_check'] = version_compare($coreVersion, $item['minimum_core_version'], '>=');
            $data['data'][$key]['humanized_last_updated_at'] = Carbon::parse($item['last_updated_at'])->diffForHumans();
        }

        return $data;
    }

    protected function listLocalPlugins(Request $request): array
    {
        $perPage = 40;
        $page = max(1, (int) $request->input('page', 1));
        $search = trim((string) $request->input('q', ''));
        $mine = $request->boolean('mine');

        $query = Alphaskyplugin::query();

        if ($mine) {
            $query->where('license', '5');
        } else {
            $query->where(function ($query) {
                $query->whereNull('license')->orWhere('license', '!=', '5');
            });
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('package_name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('author_name', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $coreVersion = get_core_version();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Alphaskyplugin $plugin) => $this->formatLocalPlugin($plugin, $coreVersion))
                ->values()
                ->all(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => $this->formatPaginationMeta($paginator),
            'error' => false,
            'message' => null,
            'additional' => [
                'advertisement' => '',
            ],
        ];
    }

    protected function formatLocalPlugin(Alphaskyplugin $plugin, string $coreVersion): array
    {
        $minimumCoreVersion = trim((string) $plugin->minimum_core_version) ?: '0.0.0';
        $updatedAt = $plugin->updated_at ?: $plugin->created_at ?: now();
        $packageName = trim((string) $plugin->package_name);

        return [
            'id' => (string) $plugin->getKey(),
            'package_name' => $packageName,
            'name' => (string) ($plugin->name ?: $packageName),
            'type' => 'plugin',
            'content' => (string) ($plugin->content ?: $plugin->description ?: ''),
            'author_name' => (string) ($plugin->author_name ?: 'Alphasky'),
            'author_url' => $plugin->author_url,
            'image_url' => $this->normalizeLocalPluginUrl((string) $plugin->image_url),
            'url' => (string) ($plugin->url ?: $plugin->source_url ?: '#'),
            'description' => (string) ($plugin->description ?: strip_tags((string) $plugin->content)),
            'screenshots' => $this->normalizeScreenshots($plugin->screenshots),
            'minimum_core_version' => $minimumCoreVersion,
            'license' => (string) ($plugin->license ?: ''),
            'license_url' => $plugin->license_url,
            'latest_version' => (string) ($plugin->latest_version ?: '1.0.0'),
            'downloads_count' => (int) $plugin->downloads_count,
            'ratings_count' => (int) $plugin->ratings_count,
            'ratings_avg' => (float) $plugin->ratings_avg,
            'last_updated_at' => Carbon::parse($updatedAt)->toJSON(),
            'can_download' => (string) $plugin->can_download === '1',
            'price' => (string) ($plugin->price ?? '0.00'),
            'source' => (string) ($plugin->source ?: 'local'),
            'buy_url' => (string) ($plugin->source_url ?: $plugin->url ?: '#'),
            'version_check' => version_compare($coreVersion, $minimumCoreVersion, '>='),
            'humanized_last_updated_at' => Carbon::parse($updatedAt)->diffForHumans(),
        ];
    }

    protected function formatPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'links' => $paginator->linkCollection()->toArray(),
            'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    protected function normalizeScreenshots(mixed $screenshots): array
    {
        if (is_string($screenshots)) {
            $decoded = json_decode($screenshots, true);
            $screenshots = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $screenshots)));
        }

        if (! is_array($screenshots)) {
            return [];
        }

        return array_values(array_map(fn ($screenshot) => $this->normalizeLocalPluginUrl((string) $screenshot), $screenshots));
    }

    protected function normalizeLocalPluginUrl(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return asset('vendor/core/core/base/images/placeholder.png');
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function detail(string $id): JsonResponse|array|null
    {
        if (class_exists(Alphaskyplugin::class)) {
            $plugin = Alphaskyplugin::query()->find($id);

            if ($plugin) {
                return ['data' => $this->formatLocalPlugin($plugin, get_core_version())];
            }
        }

        $response = $this->marketplaceService->callApi('get', '/products/' . $id);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return $response->json();
    }

    public function iframe(string $id): JsonResponse|string
    {
        $response = $this->marketplaceService->callApi('get', '/products/' . $id . '/iframe');

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return $response->body();
    }

    public function install(string $id): BaseHttpResponse
    {
        $detail = $this->detail($id);
       
        $version = $detail['data']['minimum_core_version'];
        if (version_compare($version, get_core_version(), '>')) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('packages/plugin-management::marketplace.minimum_core_version_error', compact('version')));
        }

        $name = Str::afterLast($detail['data']['package_name'], '/');

        try {
            $this->marketplaceService->beginInstall($id, $name, $this->pluginService);
        } catch (Throwable $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('packages/plugin-management::marketplace.install_success'))
            ->setData([
                'name' => $name,
                'id' => $id,
            ]);
    }

    public function rebuild(Request $request, string $id): JsonResponse|BaseHttpResponse
    {
        $request->validate([
            'json' => ['required'],
        ]);

        try {
            $response = $this->marketplaceService->callApi('post', '/products/' . $id . '/rebuild', [
                'json' => $request->input('json'),
                'alphasky_key' => trim((string) $request->header('X-Alphasky-Key', '')),
            ]);
            $data = $response instanceof JsonResponse ? $response->getData(true) : $response->json();
            $plugin = $data['data']['plugin'];
            $rebuiltId = (string) $plugin['id'];
            $name = Str::afterLast((string) $plugin['package_name'], '/');

            if (in_array($name, PluginService::getInstalledPlugins(), true)) {
                $result = $this->pluginService->updatePlugin($name, function () use ($rebuiltId, $name) {
                    $this->marketplaceService->beginInstall($rebuiltId, $name, $this->pluginService);
                    $this->pluginService->runMigrations($name);
                    $published = $this->pluginService->publishAssets($name);

                    if ($published['error']) {
                        return response()->json($published);
                    }

                    $this->pluginService->publishTranslations($name);

                    return response()->json(['error' => false]);
                });

                if ($result instanceof JsonResponse && $result->getData(true)['error'] ?? false) {
                    return $result;
                }
            } else {
                $this->marketplaceService->beginInstall($rebuiltId, $name, $this->pluginService);
            }

            return response()->json([
                'error' => false,
                'message' => (string) ($data['message'] ?? trans('packages/plugin-management::marketplace.install_success')),
                'data' => [
                    'plugin' => $plugin,
                    'name' => $name,
                    'created_copy' => (bool) ($data['data']['created_copy'] ?? false),
                ],
            ]);
        } catch (Throwable $exception) {
            $statusCode = $exception->getCode();

            return $this
                ->httpResponse()
                ->setError()
                ->setCode($statusCode >= 400 && $statusCode < 600 ? $statusCode : 500)
                ->setMessage($exception->getMessage());
        }
    }

    public function update(string $id, ?string $name = null): JsonResponse
    {
        $detail = $this->detail($id);

        if (! $name) {
            $name = Str::afterLast($detail['data']['package_name'], '/');
        }

        return $this->pluginService->updatePlugin($name, function () use ($id, $name) {
            try {
                $this->marketplaceService->beginInstall($id, $name, $this->pluginService);
            } catch (Throwable $exception) {
                return response()->json([
                    'error' => true,
                    'message' => $exception->getMessage(),
                ]);
            }

            $this->pluginService->runMigrations($name);

            $published = $this->pluginService->publishAssets($name);

            if ($published['error']) {
                return response()->json([
                    'error' => true,
                    'message' => $published['message'],
                ]);
            }

            $this->pluginService->publishTranslations($name);

            return response()->json([
                'error' => false,
                'message' => trans('packages/plugin-management::marketplace.update_success'),
                'data' => [
                    'name' => $name,
                    'id' => $id,
                ],
            ]);
        });
    }

    public function checkUpdate(): JsonResponse|array|null
    {
        $installedPlugins = $this->pluginService->getInstalledPluginIds();

        if (! $installedPlugins) {
            return response()->json();
        }

        $response = $this->marketplaceService->callApi('post', '/products/check-update', [
            'products' => $installedPlugins,
        ]);

        return $response instanceof JsonResponse ? $response : $response->json();
    }
}
