<?php

namespace Alphasky\Menu\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Menu\Commands\ClearMenuCacheCommand;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ClearMenuCacheCommand::class,
        ]);
    }
}
