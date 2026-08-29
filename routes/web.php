<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Chef\ChefController;

require __DIR__.'/auth.php';
require_once __DIR__.'/admin.php';
require_once __DIR__.'/user.php';
require_once __DIR__.'/waiter.php';
require_once __DIR__.'/chef.php';

// Shared KOT print endpoint - accessible to the owning waiter (auto-print after
// order submission) and to a chef of the order's branch (manual reprint).
Route::get('/kitchen/print/{orderCode}', [ChefController::class, 'printKot'])
    ->middleware('auth')
    ->name('kitchen.kotPrint');


// $user->token
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::redirect('/','auth/login');

Route::middleware('admin')->group(function(){
Route::get('auth/register',[AuthController::class,'registerPage'])->name('userRegister');
Route::get('auth/login',[AuthController::class,'loginPage'])->name('userLogin');

});

//login for google and github
Route::get('/auth/{provider}/redirect', [ProviderController::class,'redirect']);

Route::get('/auth/{provider}/callback', [ProviderController::class, 'callback']);







