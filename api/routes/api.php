<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SwipeController;
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


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::patch('', 'updateProfile');
            Route::get('', 'getOwnProfile');
            Route::get('/{profile}', 'getProfile');
        });
        Route::controller(ProfilePhotoController::class)->group(function () {
            Route::post('/photo', 'addPhoto');
            Route::delete('/photo/{photo}', 'deletePhoto');
        });
        Route::patch('/geolocation', [GeolocationController::class, 'updateGeolocation']);
    });
    Route::post('/swipe/{swiped_id}', [SwipeController::class, 'swipe']);

    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('', 'getUnreadNotifications');
        Route::patch('/{id}', 'markAsRead');
    });

    Route::prefix('search')->controller(SearchController::class)->group(function () {
        Route::get('/filters', 'getSearchFilters');
        Route::patch('/filters', 'updateSearchFilters');
        Route::get('/profiles', 'getProfilesByFilters');
    });
});

