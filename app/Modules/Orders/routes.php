<?php

use App\Modules\Orders\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->middleware('can:orders.view')->name('index');
    Route::get('/create', [OrderController::class, 'create'])->middleware('can:orders.create')->name('create');
    Route::post('/', [OrderController::class, 'store'])->middleware('can:orders.create')->name('store');
    Route::get('/{order}', [OrderController::class, 'show'])->middleware('can:orders.view')->name('show');
    Route::get('/{order}/edit', [OrderController::class, 'edit'])->middleware('can:orders.edit')->name('edit');
    Route::put('/{order}', [OrderController::class, 'update'])->middleware('can:orders.edit')->name('update');
});
