<?php

// student reports a lesson
use App\Http\Controllers\LessonReportController;
use Illuminate\Support\Facades\Route;

    Route::middleware(['auth:sanctum', 'role:student'])->post('/lessons/report', [LessonReportController::class, 'store']);

// admin views & manages reports
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/lesson-reports', [LessonReportController::class, 'getAllReports']);
    Route::post('/{report}/mark-as-reviewed', [LessonReportController::class, 'markAsReviewed']);
});
