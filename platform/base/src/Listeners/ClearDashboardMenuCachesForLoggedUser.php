<?php

namespace Alphasky\Base\Listeners;

use Alphasky\ACL\Models\User;
use Alphasky\Base\Facades\DashboardMenu;
use Illuminate\Auth\Events\Login;

class ClearDashboardMenuCachesForLoggedUser
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        DashboardMenu::default()->clearCachesForCurrentUser();
    }
}
