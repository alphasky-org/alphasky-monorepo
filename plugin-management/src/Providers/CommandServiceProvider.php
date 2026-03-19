<?php

namespace Alphasky\PluginManagement\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\PluginManagement\Commands\ClearCompiledCommand;
use Alphasky\PluginManagement\Commands\IlluminateClearCompiledCommand as OverrideIlluminateClearCompiledCommand;
use Alphasky\PluginManagement\Commands\PackageDiscoverCommand;
use Alphasky\PluginManagement\Commands\PluginActivateAllCommand;
use Alphasky\PluginManagement\Commands\PluginActivateCommand;
use Alphasky\PluginManagement\Commands\PluginAssetsPublishCommand;
use Alphasky\PluginManagement\Commands\PluginDeactivateAllCommand;
use Alphasky\PluginManagement\Commands\PluginDeactivateCommand;
use Alphasky\PluginManagement\Commands\PluginDiscoverCommand;
use Alphasky\PluginManagement\Commands\PluginInstallFromMarketplaceCommand;
use Alphasky\PluginManagement\Commands\PluginListCommand;
use Alphasky\PluginManagement\Commands\PluginRemoveAllCommand;
use Alphasky\PluginManagement\Commands\PluginRemoveCommand;
use Alphasky\PluginManagement\Commands\PluginUpdateVersionInfoCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand as IlluminateClearCompiledCommand;
use Illuminate\Foundation\Console\PackageDiscoverCommand as IlluminatePackageDiscoverCommand;

class CommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(IlluminatePackageDiscoverCommand::class, function () {
            return $this->app->make(PackageDiscoverCommand::class);
        });

        $this->app->extend(IlluminateClearCompiledCommand::class, function () {
            return $this->app->make(OverrideIlluminateClearCompiledCommand::class);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginAssetsPublishCommand::class,
                ClearCompiledCommand::class,
                PluginDiscoverCommand::class,
                PluginInstallFromMarketplaceCommand::class,
                PluginActivateCommand::class,
                PluginActivateAllCommand::class,
                PluginDeactivateCommand::class,
                PluginDeactivateAllCommand::class,
                PluginRemoveCommand::class,
                PluginRemoveAllCommand::class,
                PluginListCommand::class,
                PluginUpdateVersionInfoCommand::class,
            ]);
        }
    }
}
