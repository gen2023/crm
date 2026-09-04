<?php

use App\Modules\Products\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('products')->name('api.products.')->group(function () {
    Route::get('/', [ProductApiController::class, 'index'])->middleware('can:products.view')->name('index');
    Route::get('/{product}', [ProductApiController::class, 'show'])->middleware('can:products.view')->name('show');
    Route::post('/', [ProductApiController::class, 'store'])->middleware('can:products.create')->name('store');
    Route::put('/{product}', [ProductApiController::class, 'update'])->middleware('can:products.edit')->name('update');
});
