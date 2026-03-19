<?php

namespace Alphasky\Slug\Providers;

use Alphasky\Base\Events\CreatedContentEvent;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Events\FinishedSeederEvent;
use Alphasky\Base\Events\SeederPrepared;
use Alphasky\Base\Events\UpdatedContentEvent;
use Alphasky\Slug\Listeners\CreatedContentListener;
use Alphasky\Slug\Listeners\CreateMissingSlug;
use Alphasky\Slug\Listeners\DeletedContentListener;
use Alphasky\Slug\Listeners\TruncateSlug;
use Alphasky\Slug\Listeners\UpdatedContentListener;
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
        SeederPrepared::class => [
            TruncateSlug::class,
        ],
        FinishedSeederEvent::class => [
            CreateMissingSlug::class,
        ],
    ];
}
