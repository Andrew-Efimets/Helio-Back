<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $myId = auth()->id();

        $chats = Chat::whereHas('users', function ($query) use ($myId) {
            $query->where('user_id', $myId);
        })
            ->when($request->has('type') && $request->type !== null, function ($query, $type) {
                $query->where('type', $type);
            })
            ->withCount(['messages as unread_count' => function ($query) use ($myId) {
                $query->where('user_id', '!=', $myId)
                    ->whereNull('read_at');
            }])
            ->with(['users.activeAvatar', 'latestMessage.user'])
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

        $read = $chat->messages()
            ->where('user_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($read > 0) {
            broadcast(new MessageRead($chat->id, now()->toISOString()))->toOthers();
        }

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

    public function markRead(Chat $chat)
    {
        $chat->messages()
            ->where('user_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        broadcast(new MessageRead($chat->id, now()->toISOString()))->toOthers();

        return response()->json(['status' => 'success']);
    }
}
