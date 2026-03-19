<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Widgets\Contracts\AdminWidget;
use Illuminate\Foundation\Events\Dispatchable;

class RenderingAdminWidgetEvent
{
    use Dispatchable;

    public function __construct(public AdminWidget $widget)
    {
    }
}
