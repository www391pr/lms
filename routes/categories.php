<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/categories/main', [CategoryController::class, 'getMainCategories']);
    Route::get('/categories/subcategories', [CategoryController::class, 'getSubCategories']);
    Route::get('/categories', [CategoryController::class, 'getCategories']);
});
