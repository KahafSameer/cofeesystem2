<?php

use App\Http\Controllers\Cashier\CashierController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'cashier', 'middleware' => ['auth', 'cashier']], function () {
    // POS main page - Active Tickets strip + current ticket
    Route::get('/', [CashierController::class, 'index'])->name('cashier.index');

    // Ticket lifecycle
    Route::post('new', [CashierController::class, 'createDraft'])->name('cashier.new');
    Route::post('{orderCode}/suspend', [CashierController::class, 'suspendDraft'])->name('cashier.suspend');
    Route::post('{orderCode}/resume', [CashierController::class, 'resumeDraft'])->name('cashier.resume');
    Route::post('{orderCode}/discard', [CashierController::class, 'discardDraft'])->name('cashier.discard');

    // Cart actions (always target the current ticket from the session)
    Route::post('cart/add', [CashierController::class, 'addItem'])->name('cashier.cart.add');
    Route::post('cart/update', [CashierController::class, 'updateCart'])->name('cashier.cart.update');
    Route::post('cart/remove', [CashierController::class, 'removeCart'])->name('cashier.cart.remove');

    // Per-ticket order type / delivery location (persisted on the draft)
    Route::post('order-type', [CashierController::class, 'setOrderType'])->name('cashier.orderType');

    // Finalize: existing Order + PaymentRecord engine, then the draft is archived
    Route::post('{orderCode}/charge', [CashierController::class, 'charge'])->name('cashier.charge');

    // Running bills / waiter session settlement (branch-scoped)
    Route::get('sessions', [CashierController::class, 'sessions'])->name('cashier.sessions');
    Route::get('sessions/{sessionId}', [CashierController::class, 'sessionDetails'])->name('cashier.sessionDetails');
    Route::post('sessions/{sessionId}/settle', [CashierController::class, 'settleSession'])->name('cashier.settleSession');
    Route::get('sessions/{sessionId}/bill', [CashierController::class, 'sessionBill'])->name('cashier.sessionBill');
});