<?php

use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('can:users.view')->name('index');
    Route::get('/create', [UserController::class, 'create'])->middleware('can:users.create')->name('create');
    Route::post('/', [UserController::class, 'store'])->middleware('can:users.create')->name('store');
    Route::get('/{user}', [UserController::class, 'show'])->middleware('can:users.view')->name('show');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.edit')->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->middleware('can:users.edit')->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('destroy');
    Route::post('/{user}/activate', [UserController::class, 'activate'])->middleware('can:users.edit')->name('activate');
});
