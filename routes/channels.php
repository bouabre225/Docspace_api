<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
