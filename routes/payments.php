<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/payments/intent', [PaymentController::class, 'intent']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
});

Route::middleware(['auth:sanctum', 'role:instructor'])->group(function () {
    Route::post('/payouts', [PaymentController::class, 'requestPayout']);
    Route::post('/payouts/connect', [PaymentController::class, 'startExpressOnboarding']);
});
Route::get('/payouts', [PaymentController::class, 'getPayouts'])
    ->middleware(['auth:sanctum', 'role:admin|instructor']);
