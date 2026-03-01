<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $chats = Chat::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->with(['users.activeAvatar', 'messages' => function($q) {
                $q->latest()->first();
            }])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'data' => $chats
        ]);
    }

    public function store(Request $request)
    {
        $initiator = auth()->id();
        $contact = $request->contactId;
        $chat = Chat::whereHas('users', function ($q) use ($initiator) {
            $q->where('user_id', $initiator);
        })->whereHas('users', function ($q) use ($contact) {
            $q->where('user_id', $contact);
        })->where('type', 'private')
            ->first();

        if (!$chat) {
            $chat = Chat::create(['type' => 'private']);
            $chat->users()->attach([$initiator, $contact]);
        }

        return response()->json([
            'data' => [
                'id' => $chat->id,
            ]
        ]);
    }

    public function show(Chat $chat)
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
