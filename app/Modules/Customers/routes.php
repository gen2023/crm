<?php

use App\Modules\Customers\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->middleware('can:customers.view')->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->middleware('can:customers.create')->name('create');
    Route::post('/', [CustomerController::class, 'store'])->middleware('can:customers.create')->name('store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->middleware('can:customers.view')->name('show');
    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->middleware('can:customers.edit')->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('can:customers.edit')->name('update');
});
