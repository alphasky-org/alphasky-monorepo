<?php

namespace Alphasky\ACL\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnableDebugForCopilotMismatch
{
    public function handle(Request $request, Closure $next)
    {
        $copilotUserId = env('copilot_user_id');
        $currentUserId = Auth::guard()->id();
        if ($copilotUserId && $currentUserId && $copilotUserId == $currentUserId) {
        config(['app.debug' => true]);
        }

        return $next($request);
    }
}