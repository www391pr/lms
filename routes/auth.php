<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/verify', [AuthController::class, 'verifyRegister']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'googleSignIn']);

    Route::post('/password/forgot', [AuthController::class, 'sendResetCode']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'getInfo']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
