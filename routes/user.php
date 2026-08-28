<?php

use App\Http\Controllers\User\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user', 'middleware' => 'user'], function () {
    Route::get('/home', [UserDashboardController::class, 'index'])->name('userDashboard');

    Route::prefix('menu')->controller(UserDashboardController::class)->group(function () {
        Route::get('customerProfile', 'customerProfile')->name('customerProfile');
        Route::post('updateProfile/{id}', 'updateProfile')->name('updateProfile');
        Route::post('saveAddress', 'saveAddress')->name('saveAddress');

        Route::get('about', 'about')->name('about');

        //menu
        Route::get('climenu/{category_id?}', 'climenu')->name('climenu');

        // cart
        Route::get('cart', 'cartPage')->name('cartPage');
        Route::post('addToCart/{id}', 'addToCart')->name('addToCart');
        Route::post('updateCart', 'updateCart')->name('updateCart');
        Route::post('removeCart/{cartId}', 'removeCart')->name('removeCart');

        //order
        Route::post('paymentConfirm', 'paymentConfirm')->name('paymentConfirm');
        Route::get('reviewOrder', 'reviewOrder')->name('reviewOrder');

        //review
        Route::get('reviewPage', 'reviewPage')->name('reivewPage');
        Route::post('addReview', 'addReview')->name('addReview');

        //Contact
        Route::get('contactus', 'contactus')->name('contactus');
        Route::post('addContact', 'addContact')->name('addContact');

    });
});
