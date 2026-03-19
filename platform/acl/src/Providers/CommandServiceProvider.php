<?php

namespace Alphasky\ACL\Providers;

use Alphasky\ACL\Commands\UserCreateCommand;
use Alphasky\ACL\Commands\UserPasswordCommand;
use Alphasky\Base\Supports\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            UserCreateCommand::class,
            UserPasswordCommand::class,
        ]);
    }
}
