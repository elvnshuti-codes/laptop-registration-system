<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaptopRegistrationController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('registrations', LaptopRegistrationController::class);