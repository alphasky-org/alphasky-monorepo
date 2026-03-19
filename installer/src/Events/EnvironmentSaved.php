<?php

namespace Alphasky\Installer\Events;

use Alphasky\Base\Events\Event;
use Illuminate\Http\Request;

class EnvironmentSaved extends Event
{
    public function __construct(public Request $request)
    {
    }
}
