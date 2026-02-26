<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function store(User $user)
    {
        $initiator = auth()->id();
        $chat = Chat::whereHas('users', function ($q) use ($initiator) {
            $q->where('user_id', $initiator);
        })->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('type', 'private')
            ->first();

        if (!$chat) {
            $chat = Chat::create(['type' => 'private']);
            $chat->users()->attach([$initiator, $user->id]);
        }

        return response()->json([
            'data' => [
                'id' => $chat->id,
            ]
        ]);
    }

    public function show(User $user, Chat $chat)
    {
        $companion = $chat->users()
            ->where('user_id', '!=', auth()->id())
            ->first();

        return response()->json([
            'data' => [
                'id' => $chat->id,
                'companion_name' => $companion?->name,
                'companion_avatar' => $companion?->activeAvatar?->avatar_url,
                'messages' => $chat->messages()->latest()->paginate(50)
            ]
        ]);
    }
}
