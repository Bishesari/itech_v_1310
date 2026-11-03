<?php

declare(strict_types=1);

use App\Http\Controllers\SepPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/payments/sep/{paymentTransaction}/start', [SepPaymentController::class, 'start'])->name('payments.sep.start');
});
// بازگشت از درگاه
Route::post('/payments/sep/callback', [SepPaymentController::class, 'callback'])->name('payments.sep.callback');
