<?php

namespace Alphasky\ACL\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnableDebugForCopilotMismatch
{
    public function handle(Request $request, Closure $next)
    {
         

         if(! $request->user() &&  ! Auth::guard()->id()) {
            return redirect()->route('access.login')->with('error_msg', trans('core/acl::auth.back_to_login'));
         }
           
        $copilotUserId = env('copilot_user_id');
        $currentUserId = Auth::guard()->id();
        if ($copilotUserId && $currentUserId && $copilotUserId == $currentUserId) {
        config(['app.debug' => true]);
        }

        return $next($request);
    }
}