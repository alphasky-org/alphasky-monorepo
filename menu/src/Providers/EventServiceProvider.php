<?php

namespace Alphasky\Menu\Providers;

use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Menu\Listeners\DeleteMenuNodeListener;
use Alphasky\Menu\Listeners\UpdateMenuNodeUrlListener;
use Alphasky\Slug\Events\UpdatedSlugEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UpdatedSlugEvent::class => [
            UpdateMenuNodeUrlListener::class,
        ],
        DeletedContentEvent::class => [
            DeleteMenuNodeListener::class,
        ],
    ];
}
