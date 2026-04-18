<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
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
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


Route::prefix('profile')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::put('/update', 'updateProfile');
            Route::get('', 'getOwnProfile');
            Route::get('/{profile}', 'getProfile');
        });
        Route::controller(ProfilePhotoController::class)->group(function () {
            Route::post('/photo', 'addPhoto');
            Route::delete('/photo/{photo}', 'deletePhoto');
        });
    });
});

