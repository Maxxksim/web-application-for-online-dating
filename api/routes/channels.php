<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('matches.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('likes.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return $user->chats()->where('id', $chatId)->exists();
});
