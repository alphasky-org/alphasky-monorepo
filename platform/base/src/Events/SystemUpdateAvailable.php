<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Supports\ValueObjects\CoreProduct;
use Illuminate\Foundation\Events\Dispatchable;

class SystemUpdateAvailable
{
    use Dispatchable;

    public function __construct(public CoreProduct $coreProduct)
    {
    }
}
