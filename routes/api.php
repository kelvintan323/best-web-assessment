<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
});

// admin role
Route::middleware(['auth:admin'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('me', 'me');
        Route::post('logout', 'logout');
    });

    // category
    Route::get('categories', [CategoryController::class, 'index']);

    // product
    Route::get('products/export', [ProductController::class, 'export']);
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
});
