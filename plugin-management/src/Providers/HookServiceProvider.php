<?php

namespace Alphasky\PluginManagement\Providers;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Dashboard\Events\RenderingDashboardWidgets;
use Alphasky\Dashboard\Supports\DashboardWidgetInstance;
use Alphasky\PluginManagement\Services\AlphaskyMonorepoUpdater;
use Illuminate\Support\Collection;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('packages.plugin-management.general.enable_plugin_manager', true)) {
            return;
        }

        add_filter(BASE_FILTER_AFTER_SETTING_CONTENT, [$this, 'addAlphaskyMonorepoUpdateSetting'], 20);

        $this->app['events']->listen(RenderingDashboardWidgets::class, function (): void {
            add_filter(DASHBOARD_FILTER_ADMIN_NOTIFICATIONS, [$this, 'addAlphaskyMonorepoUpdateNotice'], 3);
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [$this, 'addStatsWidgets'], 15, 2);
        });
    }

    public function addAlphaskyMonorepoUpdateSetting(?string $html): ?string
    {
        if (! $this->canManageAlphaskyMonorepo()) {
            return $html;
        }

        return $html . view('packages/plugin-management::partials.alphasky-monorepo-update-card', [
            'status' => app(AlphaskyMonorepoUpdater::class)->status(),
        ])->render();
    }

    public function addAlphaskyMonorepoUpdateNotice(?string $html): ?string
    {
        if (! $this->canManageAlphaskyMonorepo()) {
            return $html;
        }

        $status = app(AlphaskyMonorepoUpdater::class)->status();

        if (! $status['update_available']) {
            return $html;
        }

        return $html . view('packages/plugin-management::partials.alphasky-monorepo-update-notice', compact('status'))->render();
    }

    protected function canManageAlphaskyMonorepo(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermission('settings.options');
    }

    public function addStatsWidgets(array $widgets, Collection $widgetSettings): array
    {
        $plugins = fn () => count(BaseHelper::scanFolder(plugin_path()));

        return (new DashboardWidgetInstance())
            ->setType('stats')
            ->setPermission('plugins.index')
            ->setTitle(trans('packages/plugin-management::plugin.plugins'))
            ->setKey('widget_total_plugins')
            ->setIcon('ti ti-plug')
            ->setColor('success')
            ->setStatsTotal($plugins)
            ->setRoute(route('plugins.index'))
            ->setColumn('col-12 col-md-6 col-lg-3')
            ->init($widgets, $widgetSettings);
    }
}
