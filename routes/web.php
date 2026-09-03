<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoutes) {
    require $moduleRoutes;
}
