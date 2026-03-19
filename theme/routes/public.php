<?php

use Alphasky\Slug\Facades\SlugHelper;
use Alphasky\Theme\Events\ThemeRoutingAfterEvent;
use Alphasky\Theme\Events\ThemeRoutingBeforeEvent;
use Alphasky\Theme\Facades\SiteMapManager;
use Alphasky\Theme\Facades\Theme;
use Alphasky\Theme\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::group(['controller' => PublicController::class], function (): void {
        event(new ThemeRoutingBeforeEvent(app()->make('router')));

        Route::get('/', 'getIndex')->name('public.index');

        if (setting('sitemap_enabled', true)) {
            Route::get('sitemap.xml', 'getSiteMap')->name('public.sitemap');

            Route::get('{key}.{extension}', 'getSiteMapIndex')
                ->whereIn('extension', SiteMapManager::allowedExtensions())
                ->name('public.sitemap.index');
        }

        Route::get('{slug?}', 'getView')->name('public.single');

        Route::get('{prefix}/{slug?}', 'getViewWithPrefix')
            ->whereIn('prefix', SlugHelper::getAllPrefixes() ?: ['1437bcd2-d94e-4a5fd-9a39-b5d60225e9af']);

        event(new ThemeRoutingAfterEvent(app()->make('router')));
    });
});
