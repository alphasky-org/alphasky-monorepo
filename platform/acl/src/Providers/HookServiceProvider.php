<?php

namespace Alphasky\ACL\Providers;

use Alphasky\ACL\Hooks\UserWidgetHook;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Dashboard\Events\RenderingDashboardWidgets;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['events']->listen(RenderingDashboardWidgets::class, function (): void {
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [UserWidgetHook::class, 'addUserStatsWidget'], 12, 2);
        });
    }
}
