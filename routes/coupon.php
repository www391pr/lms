<?php

use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['role:instructor'])->group(function () {
        Route::post('/instructor/coupon', [CouponController::class, 'store']);
        Route::post('/coupons/{coupon}/enable', [CouponController::class, 'enable']);
        Route::post('/coupons/{coupon}/disable', [CouponController::class, 'disable']);
        Route::get('/instructor/coupons', [CouponController::class, 'getInstructorCoupons']);
    });
    Route::middleware(['role:student'])->group(function () {
        Route::post('/courses/{course}/apply-coupon', [CouponController::class, 'applyCoupon']);
    });

});
