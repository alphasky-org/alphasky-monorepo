<?php

namespace Alphasky\Base\Http\Middleware;

use Alphasky\Base\Exceptions\DisabledInDemoModeException;
use Alphasky\Base\Facades\BaseHelper;
use Closure;
use Illuminate\Http\Request;

class DisableInDemoModeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (BaseHelper::hasDemoModeEnabled()) {
            throw new DisabledInDemoModeException();
        }

        return $next($request);
    }
}
