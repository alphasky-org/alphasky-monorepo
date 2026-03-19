<?php

namespace Alphasky\Theme\Providers;

use Alphasky\Base\Events\CacheCleared;
use Alphasky\Base\Events\FormRendering;
use Alphasky\Base\Events\SeederPrepared;
use Alphasky\Base\Events\SystemUpdateDBMigrated;
use Alphasky\Base\Events\SystemUpdatePublished;
use Alphasky\Theme\Listeners\AddFormJsValidation;
use Alphasky\Theme\Listeners\ClearThemeCache;
use Alphasky\Theme\Listeners\CoreUpdateThemeDB;
use Alphasky\Theme\Listeners\PublishThemeAssets;
use Alphasky\Theme\Listeners\SetDefaultTheme;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SystemUpdateDBMigrated::class => [
            CoreUpdateThemeDB::class,
        ],
        SystemUpdatePublished::class => [
            PublishThemeAssets::class,
        ],
        SeederPrepared::class => [
            SetDefaultTheme::class,
        ],
        FormRendering::class => [
            AddFormJsValidation::class,
        ],
        CacheCleared::class => [
            ClearThemeCache::class,
        ],
    ];
}
