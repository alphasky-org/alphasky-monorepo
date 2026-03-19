<?php

namespace Alphasky\PluginManagement\Providers;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Dashboard\Events\RenderingDashboardWidgets;
use Alphasky\Dashboard\Supports\DashboardWidgetInstance;
use Illuminate\Support\Collection;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('packages.plugin-management.general.enable_plugin_manager', true)) {
            return;
        }

        $this->app['events']->listen(RenderingDashboardWidgets::class, function (): void {
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [$this, 'addStatsWidgets'], 15, 2);
        });
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
