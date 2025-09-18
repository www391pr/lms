<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LessonController;
Route::middleware(['auth:sanctum'])->group(function () {

Route::prefix('lessons')->group(function () {
    // Route::middleware(['role:student'])->group(function () {
    //     Route::get('/', [LessonController::class, 'index']); // Get all lessons in section (optional)
    // });

    Route::middleware(['role:student'])->group(function () {
        Route::get('/{id}', [LessonController::class, 'show']);
        Route::post('/{id}/complete', [LessonController::class, 'completeLesson']);
    });

    Route::middleware(['role:instructor'])->group(function () {
        Route::post('/{id}', [LessonController::class, 'update']);
        Route::delete('/{id}', [LessonController::class, 'destroy']);
        Route::post('/', [LessonController::class, 'store']);
    });
});
});


