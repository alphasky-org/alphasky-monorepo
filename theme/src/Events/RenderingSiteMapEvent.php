<?php

namespace Alphasky\Theme\Events;

use Alphasky\Base\Events\Event;

class RenderingSiteMapEvent extends Event
{
    public function __construct(public ?string $key = null)
    {
    }
}
