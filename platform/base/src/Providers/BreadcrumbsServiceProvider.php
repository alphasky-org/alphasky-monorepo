<?php

namespace Alphasky\Base\Providers;

use Alphasky\Base\Supports\BreadcrumbsManager;
use Alphasky\Base\Supports\ServiceProvider;

/**
 * @deprecated This service provider does not need anymore.
 */
class BreadcrumbsServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        $this->app->singleton(BreadcrumbsManager::class);
    }

    public function provides(): array
    {
        return [BreadcrumbsManager::class];
    }
}
