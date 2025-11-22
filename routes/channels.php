<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat session channel - only session owner can listen
Broadcast::channel('chat-session.{sessionId}', function ($user, $sessionId) {
    return $user->chatSessions()->where('id', $sessionId)->exists();
});

// Trip channel - only trip owner can listen
Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    return $user->trips()->where('id', $tripId)->exists();
});
