<?php

namespace Alphasky\PluginManagement\Listeners;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Events\SystemUpdatePublished;
use Alphasky\PluginManagement\Services\MarketplaceService;
use Alphasky\PluginManagement\Services\PluginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class AutoUpdateInstalledPlugins
{
    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected PluginService $pluginService,
    ) {
    }

    public function handle(SystemUpdatePublished $event): void
    {
        if (! config('packages.plugin-management.general.auto_update_plugins_on_core_update', true)) {
            return;
        }

        $installedPlugins = $this->pluginService->getInstalledPluginIds();

        if ($installedPlugins === []) {
            return;
        }

        try {
            $response = $this->marketplaceService->callApi('post', '/products/check-update', [
                'products' => $installedPlugins,
            ]);
            $products = $response instanceof JsonResponse
                ? (array) data_get($response->getData(true), 'data', [])
                : (array) $response->json('data', []);
        } catch (Throwable $exception) {
            BaseHelper::logError($exception);

            return;
        }

        if ($products === []) {
            return;
        }

        $installedById = $this->installedPluginsById();

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $this->updatePluginFromMarketplaceProduct($product, $installedById, $installedPlugins);
        }
    }

    protected function updatePluginFromMarketplaceProduct(array $product, array $installedById, array $installedVersions): void
    {
        $id = (string) Arr::get($product, 'id', '');
        $packageName = (string) Arr::get($product, 'package_name', Arr::get($product, 'name', ''));
        $name = Str::afterLast($packageName, '/');

        if ($name === '' && $id !== '') {
            $name = (string) Arr::get($installedById, $id, '');
        }

        if ($id === '' || $name === '' || ! in_array($name, PluginService::getInstalledPlugins(), true)) {
            return;
        }

        $localVersion = (string) Arr::get($this->pluginService->getPluginInfo($name), 'version', Arr::get($installedVersions, $id, ''));

        try {
            $detailResponse = $this->marketplaceService->callApi('get', '/products/' . $id);
            $detail = $detailResponse instanceof JsonResponse
                ? (array) data_get($detailResponse->getData(true), 'data', [])
                : (array) $detailResponse->json('data', []);

            $remoteVersion = (string) Arr::get($product, 'latest_version', Arr::get($product, 'version', Arr::get($detail, 'latest_version', Arr::get($detail, 'version', ''))));
            if ($remoteVersion !== '' && $localVersion !== '' && ! version_compare($remoteVersion, $localVersion, '>')) {
                return;
            }

            $minimumCoreVersion = (string) Arr::get($detail, 'minimum_core_version', Arr::get($product, 'minimum_core_version', ''));
            if ($minimumCoreVersion !== '' && version_compare($minimumCoreVersion, get_core_version(), '>')) {
                return;
            }

            $detailPackageName = (string) Arr::get($detail, 'package_name', $packageName);
            $pluginName = Str::afterLast($detailPackageName, '/') ?: $name;

            if ($pluginName !== $name) {
                return;
            }

            $this->pluginService->updatePlugin($name, function () use ($id, $name) {
                $this->marketplaceService->beginInstall($id, $name, $this->pluginService);
                $this->pluginService->runMigrations($name);

                $published = $this->pluginService->publishAssets($name);
                if ($published['error'] ?? false) {
                    return $published;
                }

                $this->pluginService->publishTranslations($name);

                return [
                    'error' => false,
                    'message' => sprintf('Plugin %s updated successfully.', $name),
                ];
            });
        } catch (Throwable $exception) {
            BaseHelper::logError($exception);
        }
    }

    protected function installedPluginsById(): array
    {
        $plugins = [];

        foreach (PluginService::getInstalledPlugins() as $plugin) {
            $info = $this->pluginService->getPluginInfo($plugin);
            $id = (string) Arr::get($info, 'id', '');

            if ($id !== '') {
                $plugins[$id] = $plugin;
            }
        }

        return $plugins;
    }
}
