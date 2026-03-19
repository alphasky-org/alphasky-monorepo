<?php

namespace Alphasky\Theme\Events;

use Alphasky\Base\Events\Event;
use Illuminate\Queue\SerializesModels;

class ThemeRemoveEvent extends Event
{
    use SerializesModels;

    public function __construct(public string $theme)
    {
    }
}
