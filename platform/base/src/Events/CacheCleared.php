<?php

namespace Alphasky\Base\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CacheCleared extends Event
{
    use Dispatchable;

    public function __construct(public string $cacheType = 'all')
    {
    }
}
