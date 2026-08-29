<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Waiter\WaiterController;

Route::group(['prefix' => 'waiter', 'middleware' => ['auth', 'waiter']], function () {
    Route::get('/dashboard', [WaiterController::class, 'dashboard'])->name('waiter.dashboard');

    Route::prefix('order')->controller(WaiterController::class)->group(function () {
        Route::get('new', 'newOrder')->name('waiter.newOrder');
        Route::post('add', 'addToCart')->name('waiter.addToCart');
        Route::get('cart', 'cart')->name('waiter.cart');
        Route::post('cart/update', 'updateCart')->name('waiter.updateCart');
        Route::post('cart/remove/{cartId}', 'removeCart')->name('waiter.removeCart');
        Route::post('place', 'placeOrder')->name('waiter.placeOrder');
        Route::get('current', 'currentOrders')->name('waiter.currentOrders');
        Route::get('history', 'orderHistory')->name('waiter.orderHistory');

        // Edit pending order (editable until completed)
        Route::post('item/update', 'updateOrderItem')->name('waiter.updateOrderItem');
        Route::post('item/remove', 'removeOrderItem')->name('waiter.removeOrderItem');
        Route::get('{orderCode}/edit', 'editOrder')->name('waiter.editOrder');
        Route::post('{orderCode}/add', 'addToOrder')->name('waiter.addToOrder');
        Route::post('{orderCode}/meta', 'updateOrderMeta')->name('waiter.updateOrderMeta');

        Route::get('{orderCode}', 'orderDetails')->name('waiter.orderDetails');
    });

    Route::prefix('profile')->controller(WaiterController::class)->group(function () {
        Route::get('/', 'profile')->name('waiter.profile');
    });

    // Customer Session / Running Bill
    Route::prefix('sessions')->controller(WaiterController::class)->group(function () {
        Route::get('/', 'sessions')->name('waiter.sessions');
        Route::post('/', 'createSession')->name('waiter.createSession');
        Route::get('{sessionId}/menu', 'sessionNewOrder')->name('waiter.sessionNewOrder');
        Route::post('{sessionId}/orders', 'placeSessionOrder')->name('waiter.placeSessionOrder');
        Route::post('{sessionId}/bill', 'requestBill')->name('waiter.requestBill');
        Route::get('{sessionId}', 'sessionDetails')->name('waiter.sessionDetails');
    });
});
