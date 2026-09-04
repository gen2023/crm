<?php

use App\Modules\Settings\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'edit'])->middleware('can:settings.edit')->name('edit');
    Route::put('/', [SettingsController::class, 'update'])->middleware('can:settings.edit')->name('update');
});
