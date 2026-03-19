<?php

use Alphasky\ACL\Http\Controllers\Auth\ForgotPasswordController;
use Alphasky\ACL\Http\Controllers\Auth\LoginController;
use Alphasky\ACL\Http\Controllers\Auth\ResetPasswordController;
use Alphasky\ACL\Http\Middleware\CheckUserUpdatePermission;
use Alphasky\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Alphasky\ACL\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['middleware' => 'guest'], function (): void {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('access.login');
            Route::post('login', [LoginController::class, 'login'])->name('access.login.post');

            Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
                ->name('access.password.request');
            Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                ->name('access.password.email');

            Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
                ->name('access.password.reset');
            Route::post('password/reset', [ResetPasswordController::class, 'reset'])
                ->name('access.password.reset.post');
        });
    }, ['web', 'core']);

    AdminHelper::registerRoutes(function (): void {
        Route::get('logout', [
            'as' => 'access.logout',
            'uses' => 'Auth\LoginController@logout',
            'permission' => false,
        ]);

        Route::group(['prefix' => 'system'], function (): void {
            Route::group(['prefix' => 'users', 'as' => 'users.'], function (): void {
                Route::resource('', 'UserController')->except(['edit', 'update'])->parameters(['' => 'user']);

                Route::post('modify-profile-image/{user}', [
                    'as' => 'profile.image',
                    'uses' => 'UserController@postAvatar',
                    'permission' => false,
                ])->wherePrimaryKey();

                Route::post('delete-profile-image/{user}', [
                    'as' => 'profile.image.destroy',
                    'uses' => 'UserController@removeAvatar',
                    'permission' => false,
                ])->wherePrimaryKey();

                Route::middleware(CheckUserUpdatePermission::class)->group(function (): void {
                    Route::put('password/{user}', [
                        'as' => 'change-password',
                        'uses' => 'UserController@postChangePassword',
                        'permission' => false,
                        'middleware' => 'preventDemo',
                    ])->wherePrimaryKey('user');

                    Route::get('profile/{user}', [
                        'as' => 'profile.view',
                        'uses' => 'UserController@getUserProfile',
                        'permission' => false,
                    ])->wherePrimaryKey('user');

                    Route::put('profile/{user}', [
                        'as' => 'update-profile',
                        'uses' => 'UserController@postUpdateProfile',
                        'permission' => false,
                        'middleware' => 'preventDemo',
                    ])->wherePrimaryKey('user');

                    Route::put('profile/{user}/preferences', [
                        'as' => 'update-preferences',
                        'uses' => 'UserController@updatePreferences',
                        'permission' => false,
                        'middleware' => 'preventDemo',
                    ])->wherePrimaryKey('user');

                    Route::patch('profile/{user}/preferences', [
                        'uses' => 'UserController@patchUpdatePreferences',
                        'permission' => false,
                    ])->wherePrimaryKey('user');
                });

                Route::get('make-super/{user}', [
                    'as' => 'make-super',
                    'uses' => 'UserController@makeSuper',
                    'permission' => ACL_ROLE_SUPER_USER,
                ])->wherePrimaryKey('user');

                Route::get('remove-super/{user}', [
                    'as' => 'remove-super',
                    'uses' => 'UserController@removeSuper',
                    'permission' => ACL_ROLE_SUPER_USER,
                    'middleware' => 'preventDemo',
                ])->wherePrimaryKey('user');
            });

            Route::group(['prefix' => 'roles', 'as' => 'roles.'], function (): void {
                Route::resource('', 'RoleController')->parameters(['' => 'role']);

                Route::get('duplicate/{role}', [
                    'as' => 'duplicate',
                    'uses' => 'RoleController@getDuplicate',
                    'permission' => 'roles.create',
                ])->wherePrimaryKey('role');

                Route::get('json', [
                    'as' => 'list.json',
                    'uses' => 'RoleController@getJson',
                    'permission' => 'roles.index',
                ]);

                Route::post('assign', [
                    'as' => 'assign',
                    'uses' => 'RoleController@postAssignMember',
                    'permission' => 'roles.edit',
                ]);
            });
        });
    });
});
