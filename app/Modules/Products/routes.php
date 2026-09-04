<?php

use App\Modules\Products\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->middleware('can:products.view')->name('index');
    Route::get('/create', [ProductController::class, 'create'])->middleware('can:products.create')->name('create');
    Route::post('/', [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
    Route::get('/{product}', [ProductController::class, 'show'])->middleware('can:products.view')->name('show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('can:products.edit')->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:products.edit')->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('can:products.delete')->name('destroy');
});
