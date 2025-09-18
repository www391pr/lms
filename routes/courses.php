<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

Route::middleware(['auth:sanctum'])->prefix('courses')->group(function () {
    Route::middleware(['role:instructor'])->group(function () {
        Route::post('/', [CourseController::class, 'store']);
        Route::post('/{id}', [CourseController::class, 'update']);
        Route::put('/{course}/sections/order', [CourseController::class, 'updateSectionsOrder']);
//        Route::delete('/{id}', [CourseController::class, 'destroy']);
    });

    Route::middleware(['role:student'])->group(function () {
        Route::get('/{id}/view', [CourseController::class, 'addView']);
        Route::post('/{id}/rate', [CourseController::class, 'rate']);
        Route::post('/{id}/review', [CourseController::class, 'review']);
    });
    Route::get('/{id}', [CourseController::class, 'show'])->middleware(['role:student|instructor']);
    Route::get('/', [CourseController::class, 'getCourses']);
});
