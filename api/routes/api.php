<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Middleware\AuthorizedForGuestOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware([AuthorizedForGuestOnly::class])->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('/login', 'login');
            Route::post('/register', 'register');
        });
        Route::controller(GoogleAuthController::class)->group(function () {
            Route::get('google/redirect', 'redirectToGoogle');
            Route::get('google/callback', 'handleGoogleCallback');
        });
    });
});


