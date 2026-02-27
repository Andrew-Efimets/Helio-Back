<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chats.{id}', function ($user, $id) {
    return Chat::where('id', (int) $id)
        ->whereHas('users', function($q) use ($user) {
            $q->where('users.id', (int) $user->id);
        })->exists();
});
