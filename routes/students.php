<?php

use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/student/select-categories', [StudentController::class, 'attachMainCategories']);
    Route::post('/student/select-subcategories', [StudentController::class, 'attachSubCategories']);
    Route::get('/student/courses', [StudentController::class, 'getStudentCourses'])->name('student.courses');
    Route::get('/student/categories', [StudentController::class, 'getStudentCategories'])->name('student.categories');

    Route::post('/courses/{course}/wishlist', [StudentController::class, 'addToWishlist']);


    Route::get('/lessons/{lesson}/comments', [CommentController::class, 'index']);
    Route::post('/lessons/{lesson}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

});
