<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileInterestController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SwipeController;
use App\Http\Middleware\AuthorizedForGuestOnly;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

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
    Route::delete('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profiles')->group(function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::patch('/me', 'updateProfile');
            Route::get('/me', 'getMyProfile');
            Route::get('/{profile}', 'getProfile');
            Route::patch('/me/enable', 'enableProfile');
            Route::patch('/me/disable', 'disableProfile');
        });
        Route::controller(ProfilePhotoController::class)->group(function () {
            Route::post('/photos', 'addPhoto');
            Route::delete('/photos/{photo}', 'deletePhoto');
        });

        Route::controller(ProfileInterestController::class)->group(function () {
            Route::post('/interests', 'addInterest');
            Route::delete('/interests/{interest}', 'deleteInterest');
        });
    });

    Route::patch('/location', [LocationController::class, 'updateLocation']);

    Route::post('/swipes/{swiped_id}', [SwipeController::class, 'swipe']);
    Route::delete('/swipes/{swiped_id}', [SwipeController::class, 'rollbackSwipe']);
    Route::get('/likes', [LikeController::class, 'getLikes']);
    Route::get('/matches', [MatchController::class, 'getMatches']);

    Route::controller(NotificationController::class)->group(function () {
        Route::get('notifications', 'getUnreadNotifications');
        Route::patch('notifications/{notification}', 'markAsRead');
    });

    Route::prefix('search')->controller(SearchController::class)->group(function () {
        Route::get('/filters', 'getSearchFilters');
        Route::patch('/filters', 'updateSearchFilters');
        Route::get('/profiles', 'getProfilesByFilters');
    });

    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::get('/chats/{chat}/messages', [MessageController::class, 'getMessages']);
    Route::patch('chats/{chat}/messages', [MessageController::class, 'markAsRead']);
    Route::post('/chats/{recipient}/messages', [MessageController::class, 'sendMessage']);

    Route::prefix('subscription')->controller(SubscriptionController::class)->group(function () {
        Route::post('/checkout', 'checkout');
        Route::get('/status', 'status');
        Route::delete('/cancel', 'cancel');
    });
});

Route::post('subscription/stripe/webhook', [WebhookController::class, 'handleWebhook']);

