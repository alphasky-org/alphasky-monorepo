<?php

namespace Alphasky\SeoHelper\Providers;

use Alphasky\Base\Events\CreatedContentEvent;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Events\UpdatedContentEvent;
use Alphasky\SeoHelper\Listeners\CreatedContentListener;
use Alphasky\SeoHelper\Listeners\DeletedContentListener;
use Alphasky\SeoHelper\Listeners\UpdatedContentListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UpdatedContentEvent::class => [
            UpdatedContentListener::class,
        ],
        CreatedContentEvent::class => [
            CreatedContentListener::class,
        ],
        DeletedContentEvent::class => [
            DeletedContentListener::class,
        ],
    ];
}
