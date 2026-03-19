<?php

namespace Alphasky\Icon\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Icon\Commands\IconUpdateCommand;
use Alphasky\Icon\Facades\Icon as IconFacade;
use Alphasky\Icon\IconManager;
use Alphasky\Icon\View\Components\Icon;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;

class IconServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this
            ->setNamespace('core/icon')
            ->loadAndPublishConfigurations('icon');

        $this->app->singleton(IconManager::class);
    }

    public function boot(): void
    {
        Blade::component('core::icon', Icon::class);

        $aliasLoader = AliasLoader::getInstance();

        if (! class_exists('CoreIcon')) {
            $aliasLoader->alias('CoreIcon', IconFacade::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([IconUpdateCommand::class]);
        }
    }
}
