<?php

use Alphasky\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['prefix' => 'short-codes', 'namespace' => 'Alphasky\Shortcode\Http\Controllers'], function (): void {
        Route::post('ajax-get-admin-config/{key}', [
            'as' => 'short-codes.ajax-get-admin-config',
            'uses' => 'ShortcodeController@ajaxGetAdminConfig',
            'permission' => false,
        ]);
    });
});

