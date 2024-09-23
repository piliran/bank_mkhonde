<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::routes();

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    Log::info("User trying to join channel: ", [$user->id, $id]);
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications', function ($user) {
    return true;
});