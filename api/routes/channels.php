<?php

use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('likesRetracted.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('chats.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});



