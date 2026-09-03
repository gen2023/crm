<?php

use App\Modules\Roles\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('can:roles.view')->name('index');
    Route::get('/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('create');
    Route::post('/', [RoleController::class, 'store'])->middleware('can:roles.create')->name('store');
    Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:roles.view')->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.edit')->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:roles.edit')->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('destroy');
});
