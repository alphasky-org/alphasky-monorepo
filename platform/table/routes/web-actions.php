<?php

use Alphasky\Table\Http\Controllers\TableBulkActionController;
use Alphasky\Table\Http\Controllers\TableBulkChangeController;
use Alphasky\Table\Http\Controllers\TableColumnVisibilityController;
use Alphasky\Table\Http\Controllers\TableFilterController;
use Alphasky\Table\Http\Controllers\TableInlineEditController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tables', 'permission' => false, 'as' => 'table.'], function (): void {
    Route::group(['prefix' => 'bulk-changes', 'as' => 'bulk-change.'], function (): void {
        Route::get('data', [TableBulkChangeController::class, 'index'])->name('data');
        Route::post('save', [TableBulkChangeController::class, 'update'])->name('save');
    });

    Route::group(['prefix' => 'bulk-actions', 'as' => 'bulk-action.'], function (): void {
        Route::post('/', [TableBulkActionController::class, '__invoke'])->name('dispatch');
    });

    Route::group(['prefix' => 'filters', 'as' => 'filter.'], function (): void {
        Route::get('/', [TableFilterController::class, '__invoke'])->name('input');
    });
    Route::get('inline-edit', [TableInlineEditController::class, 'inlineEdit'])->name('table.inline-edit');
   
    Route::group(['middleware' => 'preventDemo', 'prefix' => 'columns-visibility'], function (): void {
        Route::put('/', [TableColumnVisibilityController::class, 'update'])->name('update-columns-visibility');
    });
});
