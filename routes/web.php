<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Minimal placeholder landing page for authenticated users. Replaced by the
// real Dashboard module in a later step (see docs/PHASE-1-SPEC.md).
Route::get('/dashboard', function () {
    return view('dashboard', ['user' => auth()->user()]);
})->middleware('auth')->name('dashboard');

foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoutes) {
    require $moduleRoutes;
}
