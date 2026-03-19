<?php

namespace Alphasky\Shortcode\Providers;

use Alphasky\Base\Facades\AdminHelper;
use Alphasky\Base\Supports\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(DASHBOARD_FILTER_ADMIN_NOTIFICATIONS, function (?string $html): ?string {
            if (! AdminHelper::isInAdmin(true) || ! Auth::check()) {
                return $html;
            }

            if (Route::currentRouteName() === 'settings.cache') {
                return $html;
            }

            if (setting('shortcode_cache_enabled', false)) {
                return $html;
            }

            if (isset($_COOKIE['shortcode_cache_suggestion_dismissed']) && $_COOKIE['shortcode_cache_suggestion_dismissed'] == '1') {
                return $html;
            }

            return $html . view('packages/shortcode::partials.shortcode-cache-suggestion')->render();
        }, 5);
    }
}
