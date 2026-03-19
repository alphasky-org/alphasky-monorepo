<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Supports\DashboardMenu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

class DashboardMenuRetrieved
{
    use Dispatchable;

    public function __construct(
        public DashboardMenu $dashboardMenu,
        public Collection $menuItems
    ) {
    }
}
