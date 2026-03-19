<?php

namespace Alphasky\ACL\Providers;

use Alphasky\ACL\Events\RoleAssignmentEvent;
use Alphasky\ACL\Events\RoleUpdateEvent;
use Alphasky\ACL\Listeners\LoginListener;
use Alphasky\ACL\Listeners\RoleAssignmentListener;
use Alphasky\ACL\Listeners\RoleUpdateListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RoleUpdateEvent::class => [
            RoleUpdateListener::class,
        ],
        RoleAssignmentEvent::class => [
            RoleAssignmentListener::class,
        ],
        Login::class => [
            LoginListener::class,
        ],
    ];
}
