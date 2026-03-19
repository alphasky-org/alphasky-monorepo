<?php

namespace Alphasky\Installer\Providers;

use Alphasky\Base\Events\FinishedSeederEvent;
use Alphasky\Base\Events\UpdatedEvent;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Installer\Http\Middleware\CheckIfInstalledMiddleware;
use Alphasky\Installer\Http\Middleware\CheckIfInstallingMiddleware;
use Alphasky\Installer\Http\Middleware\RedirectIfNotInstalledMiddleware;
use Carbon\Carbon;
use Illuminate\Routing\Events\RouteMatched;

class InstallerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('packages/installer')
            ->loadHelpers()
            ->loadAndPublishConfigurations('installer')
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes()
            ->publishAssets();

        $this->app['events']->listen(RouteMatched::class, function (): void {
            if (defined('INSTALLED_SESSION_NAME')) {
                $router = $this->app->make('router');

                $router->middlewareGroup('install', [CheckIfInstalledMiddleware::class]);
                $router->middlewareGroup('installing', [CheckIfInstallingMiddleware::class]);

                $router->pushMiddlewareToGroup('web', RedirectIfNotInstalledMiddleware::class);
            }
        });

        $this->app['events']->listen([UpdatedEvent::class, FinishedSeederEvent::class], function (): void {
            BaseHelper::saveFileData(storage_path(INSTALLED_SESSION_NAME), Carbon::now()->toDateTimeString());
        });
    }
}
