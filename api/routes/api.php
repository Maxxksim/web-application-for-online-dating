<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\AuthorizedForGuestOnly;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->middleware([AuthorizedForGuestOnly::class])->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
});

