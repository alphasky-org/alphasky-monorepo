<?php

namespace Alphasky\PluginManagement\Providers;

use Alphasky\Base\Events\SeederPrepared;
use Alphasky\Base\Events\SystemUpdateDBMigrated;
use Alphasky\Base\Events\SystemUpdatePublished;
use Alphasky\Base\Listeners\ClearDashboardMenuCaches;
use Alphasky\PluginManagement\Events\ActivatedPluginEvent;
use Alphasky\PluginManagement\Events\UpdatedPluginEvent;
use Alphasky\PluginManagement\Events\UpdatingPluginEvent;
use Alphasky\PluginManagement\Listeners\ActivateAllPlugins;
use Alphasky\PluginManagement\Listeners\ClearPluginCaches;
use Alphasky\PluginManagement\Listeners\CoreUpdatePluginsDB;
use Alphasky\PluginManagement\Listeners\PublishPluginAssets;
use Illuminate\Contracts\Database\Events\MigrationEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MigrationEvent::class => [
            ClearPluginCaches::class,
        ],
        SystemUpdateDBMigrated::class => [
            CoreUpdatePluginsDB::class,
        ],
        SystemUpdatePublished::class => [
            PublishPluginAssets::class,
        ],
        SeederPrepared::class => [
            ActivateAllPlugins::class,
        ],
        ActivatedPluginEvent::class => [
            ClearDashboardMenuCaches::class,
        ],
        UpdatingPluginEvent::class => [
            ClearPluginCaches::class,
        ],
        UpdatedPluginEvent::class => [
            ClearDashboardMenuCaches::class,
        ],
    ];
}
