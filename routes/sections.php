<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SectionController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['role:student'])->group(function () {
        Route::get('/courses/{course}/sections', [SectionController::class, 'getAllSections']);
        Route::get('/sections/{id}/lessons', [SectionController::class, 'lessons']);
    });

    Route::middleware(['role:instructor'])->prefix('sections')->group(function () {
        Route::post('/', [SectionController::class, 'store']);
        Route::put('/{id}', [SectionController::class, 'update']);
        Route::delete('/{id}', [SectionController::class, 'destroy']);
        Route::put('/{id}/lessons/order', [SectionController::class, 'updateLessonsOrder']);
    });
});
