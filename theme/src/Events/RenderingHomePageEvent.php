<?php

namespace Alphasky\Theme\Events;

use Alphasky\Base\Events\Event;
use Illuminate\Queue\SerializesModels;

class RenderingHomePageEvent extends Event
{
    use SerializesModels;
}
