<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Chef\ChefController;

Route::group(['prefix' => 'chef', 'middleware' => ['auth', 'chef']], function () {
    Route::get('/dashboard', [ChefController::class, 'dashboard'])->name('chef.dashboard');

    Route::prefix('order')->controller(ChefController::class)->group(function () {
        Route::get('new', 'newOrders')->name('chef.newOrders');
        Route::get('preparing', 'preparing')->name('chef.preparing');
        Route::get('ready', 'ready')->name('chef.ready');
        Route::get('history', 'history')->name('chef.history');

        Route::post('{orderCode}/start', 'startPreparing')->name('chef.startPreparing');
        Route::post('{orderCode}/ready', 'markReady')->name('chef.markReady');
        Route::get('{orderCode}', 'orderDetails')->name('chef.orderDetails');
    });
});
