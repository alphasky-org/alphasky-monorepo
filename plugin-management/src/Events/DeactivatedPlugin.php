<?php

namespace Alphasky\PluginManagement\Events;

use Alphasky\Base\Events\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeactivatedPlugin extends Event
{
    use SerializesModels;
    use Dispatchable;

    public function __construct(public string $plugin)
    {
    }
}
