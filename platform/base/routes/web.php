<?php

use Alphasky\Base\Facades\AdminHelper;
use Alphasky\Base\Http\Controllers\AudioUploadController;
use Alphasky\Base\Http\Controllers\SignatureUploadController;
use Alphasky\Base\Http\Controllers\CacheManagementController;
use Alphasky\Base\Http\Controllers\CoreIconController;
use Alphasky\Base\Http\Controllers\NotificationController;
use Alphasky\Base\Http\Controllers\SearchController;
use Alphasky\Base\Http\Controllers\SystemInformationController;
use Alphasky\Base\Http\Controllers\ToggleThemeModeController;
use Alphasky\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Alphasky\Base\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['prefix' => 'system'], function (): void {
            Route::get('', [
                'as'         => 'system.index',
                'uses'       => 'SystemController@getIndex',
                'permission' => 'core.system',
            ]);
        });

        Route::group(['permission' => 'superuser'], function (): void {
            Route::prefix('system/info')->group(function (): void {
                Route::match(['GET', 'POST'], '/', [SystemInformationController::class, 'index'])
                    ->name('system.info');
                Route::get('get-addition-data', [SystemInformationController::class, 'getAdditionData'])
                    ->middleware(RequiresJsonRequestMiddleware::class)
                    ->name('system.info.get-addition-data');
            });

            Route::prefix('system/cache')->group(function (): void {
                Route::get('', [CacheManagementController::class, 'index'])->name('system.cache');
                Route::post('clear', [CacheManagementController::class, 'destroy'])
                    ->name('system.cache.clear')
                    ->middleware('preventDemo');
            });
        });

        Route::get('system/cleanup', [
            'as'         => 'system.cleanup',
            'uses'       => 'SystemController@getCleanup',
            'permission' => 'superuser',
        ]);

        Route::post('system/cleanup', [
            'as'         => 'system.cleanup.process',
            'uses'       => 'SystemController@getCleanup',
            'permission' => 'superuser',
            'middleware' => 'preventDemo',
        ]);

        Route::post('system/debug-mode/turn-off', [
            'as'         => 'system.debug-mode.turn-off',
            'uses'       => 'DebugModeController@postTurnOff',
            'permission' => 'superuser',
            'middleware' => 'preventDemo',
        ]);

        Route::get('system/cronjob', [
            'as'   => 'system.cronjob',
            'uses' => 'CronjobSettingController@index',
        ]);

        Route::get('system/security', [
            'as'   => 'system.security',
            'uses' => 'SecuritySettingController@index',
        ]);

        Route::group(['permission' => false], function (): void {
            Route::post('membership/authorize', [
                'as'   => 'membership.authorize',
                'uses' => 'SystemController@postAuthorize',
            ]);

            Route::get('menu-items-count', [
                'as'   => 'menu-items-count',
                'uses' => 'SystemController@getMenuItemsCount',
            ]);

            Route::group(
                ['prefix' => 'notifications', 'as' => 'notifications.', 'controller' => NotificationController::class],
                function (): void {
                    Route::get('/', [
                        'as'   => 'index',
                        'uses' => 'index',
                    ]);

                    Route::delete('{id}', [
                        'as'   => 'destroy',
                        'uses' => 'destroy',
                    ])->wherePrimaryKey();

                    Route::get('read-notification/{id}', [
                        'as'   => 'read-notification',
                        'uses' => 'read',
                    ])->wherePrimaryKey();

                    Route::put('read-all-notification', [
                        'as'   => 'read-all-notification',
                        'uses' => 'readAll',
                    ]);

                    Route::delete('destroy-all-notification', [
                        'as'   => 'destroy-all-notification',
                        'uses' => 'deleteAll',
                    ]);

                    Route::get('count-unread', [
                        'as'   => 'count-unread',
                        'uses' => 'countUnread',
                    ]);
                }
            );

            Route::get('toggle-theme-mode', [ToggleThemeModeController::class, '__invoke'])->name('toggle-theme-mode');

            Route::get('search', [SearchController::class, '__invoke'])->name('core.global-search');

            Route::get('core-icons', [CoreIconController::class, 'index'])
                ->name('core-icons')
                ->middleware(RequiresJsonRequestMiddleware::class);
                
            // Audio upload routes
            Route::prefix('audio')->name('audio.')->group(function (): void {
                Route::post('upload', [AudioUploadController::class, 'upload'])->name('upload');
                Route::delete('delete', [AudioUploadController::class, 'delete'])->name('delete');
                Route::get('info', [AudioUploadController::class, 'info'])->name('info');
            });
            
            // Signature upload routes
            Route::prefix('signature')->name('signature.')->group(function (): void {
                Route::post('upload', [SignatureUploadController::class, 'upload'])->name('upload');
                Route::delete('delete', [SignatureUploadController::class, 'delete'])->name('delete');
                Route::get('info', [SignatureUploadController::class, 'info'])->name('info');
            });
        });
    });
});
