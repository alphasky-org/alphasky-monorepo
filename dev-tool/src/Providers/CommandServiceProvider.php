<?php

namespace Alphasky\DevTool\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\DevTool\Commands\LocaleCreateCommand;
use Alphasky\DevTool\Commands\LocaleRemoveCommand;
use Alphasky\DevTool\Commands\Make\ControllerMakeCommand;
use Alphasky\DevTool\Commands\Make\FormMakeCommand;
use Alphasky\DevTool\Commands\Make\ModelMakeCommand;
use Alphasky\DevTool\Commands\Make\PanelSectionMakeCommand;
use Alphasky\DevTool\Commands\Make\RequestMakeCommand;
use Alphasky\DevTool\Commands\Make\RouteMakeCommand;
use Alphasky\DevTool\Commands\Make\SettingControllerMakeCommand;
use Alphasky\DevTool\Commands\Make\SettingFormMakeCommand;
use Alphasky\DevTool\Commands\Make\SettingMakeCommand;
use Alphasky\DevTool\Commands\Make\SettingRequestMakeCommand;
use Alphasky\DevTool\Commands\Make\TableMakeCommand;
use Alphasky\DevTool\Commands\PackageCreateCommand;
use Alphasky\DevTool\Commands\PackageMakeCrudCommand;
use Alphasky\DevTool\Commands\PackageRemoveCommand;
use Alphasky\DevTool\Commands\PluginCreateCommand;
use Alphasky\DevTool\Commands\PluginMakeCrudCommand;
use Alphasky\DevTool\Commands\RebuildPermissionsCommand;
use Alphasky\DevTool\Commands\TestSendMailCommand;
use Alphasky\DevTool\Commands\ThemeCreateCommand;
use Alphasky\DevTool\Commands\WidgetCreateCommand;
use Alphasky\DevTool\Commands\WidgetRemoveCommand;
use Alphasky\PluginManagement\Providers\PluginManagementServiceProvider;
use Alphasky\Theme\Providers\ThemeServiceProvider;
use Alphasky\Widget\Providers\WidgetServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            TableMakeCommand::class,
            ControllerMakeCommand::class,
            RouteMakeCommand::class,
            RequestMakeCommand::class,
            FormMakeCommand::class,
            ModelMakeCommand::class,
            PackageCreateCommand::class,
            PackageMakeCrudCommand::class,
            PackageRemoveCommand::class,
            TestSendMailCommand::class,
            RebuildPermissionsCommand::class,
            LocaleRemoveCommand::class,
            LocaleCreateCommand::class,
        ]);

        if (version_compare(get_core_version(), '7.0.0', '>=')) {
            $this->commands([
                PanelSectionMakeCommand::class,
                SettingControllerMakeCommand::class,
                SettingRequestMakeCommand::class,
                SettingFormMakeCommand::class,
                SettingMakeCommand::class,
            ]);
        }

        if (class_exists(PluginManagementServiceProvider::class)) {
            $this->commands([
                PluginCreateCommand::class,
                PluginMakeCrudCommand::class,
            ]);
        }

        if (class_exists(ThemeServiceProvider::class)) {
            $this->commands([
                ThemeCreateCommand::class,
            ]);
        }

        if (class_exists(WidgetServiceProvider::class)) {
            $this->commands([
                WidgetCreateCommand::class,
                WidgetRemoveCommand::class,
            ]);
        }
    }
}
