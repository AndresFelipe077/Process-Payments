<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('payments/pay', [PaymentController::class, 'pay'])->name('pay');
Route::post('payments/approval', [PaymentController::class, 'approval'])->name('approval');
Route::post('payments/cancelled', [PaymentController::class, 'cancelled'])->name('cancelled');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
