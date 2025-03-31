<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


use App\Models\User;




Broadcast::channel('App.Models.User.{id}', function ($user, $id) {


    return (int) $user->id === (int) $id;
});



Broadcast::channel('notifications', function (User $user) {


    return true;
});


Broadcast::channel('user-status.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId; // Check if the authenticated user can access the channel
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {

    $chat = \App\Models\Chat::find($chatId);
    if ($chat) {
        $isAuthorized = $chat->lender_id === $user->id || $chat->borrower_id === $user->id;
        Log::info("User {$user->id} authorized: {$isAuthorized}");
        return $isAuthorized;
    }


    return false;
});


Broadcast::channel('presence.{chatId}', function ($user, $chatId) {
    $chat = \App\Models\Chat::find($chatId);
    if ($chat && ($chat->lender_id === $user->id || $chat->borrower_id === $user->id)) {
        return ['id' => $user->id]; // Return user ID for presence
    }
    return false;
});

