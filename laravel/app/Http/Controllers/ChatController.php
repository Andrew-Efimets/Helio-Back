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
        $messages = $chat->messages()
            ->with('user')
            ->latest()
            ->paginate(20);

        $messages->setCollection(
            $messages->getCollection()->reverse()->values()
        );

        $participants = $chat->users()
            ->with('activeAvatar')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'avatar' => $participant->activeAvatar?->avatar_url,
                ];
            });

        return response()->json([
            'data' => [
                'id' => $chat->id,
                'type' => $chat->type,
                'messages' => $messages,
                'participants' => $participants,
            ]
        ]);
    }
}
