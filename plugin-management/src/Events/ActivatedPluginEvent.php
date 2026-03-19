<?php

namespace Alphasky\PluginManagement\Events;

use Alphasky\Base\Events\Event;
use Illuminate\Queue\SerializesModels;

class ActivatedPluginEvent extends Event
{
    use SerializesModels;

    public function __construct(public string $plugin)
    {
    }
}
