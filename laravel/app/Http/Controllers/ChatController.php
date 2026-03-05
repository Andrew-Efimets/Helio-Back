<?php

namespace App\Http\Controllers;

use App\Events\ChatCreated;
use App\Events\MessageRead;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $myId = auth()->id();

        $chats = Chat::whereHas('users', function ($query) use ($myId) {
            $query->where('user_id', $myId)
                ->whereNull('chat_user.deleted_at');
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

        broadcast(new ChatCreated($chat->id, $contact))->toOthers();

        return response()->json([
            'data' => [
                'id' => $chat->id,
            ]
        ]);
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $chat = Chat::create([
            'type'    => 'group',
            'title'   => $validated['title'],
        ]);

        if ($request->hasFile('avatar')) {
            $folder = now()->format('Y/m/'). 'chat' . $chat->id . '/avatar';
            $path = $request->file('avatar')->store($folder, 's3');
            $chat->avatar = Storage::disk('s3')->url($path);
            $chat->save();
        }

        $chat->users()->attach(auth()->id(), [
            'role'   => 'admin',
            'status' => 'active'
        ]);

        return response()->json([
            'data' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'avatar' => $chat->avatar,
            ]
        ], 201);
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

    public function leaveChat(Chat $chat)
    {
        $myId = auth()->id();

        if ($chat->type === 'group') {
            $me = $chat->users()->where('users.id', $myId)->first();

            if (!$me) {
                return response()->json(['message' => 'Вы не являетесь участником чата'], 403);
            }

            if ($me->pivot->role === 'admin') {
                $chat->delete();
                return response()->json(['message' => 'Группа удалена']);
            }

            $chat->users()->updateExistingPivot($myId, ['deleted_at' => now()]);
            return response()->json(['message' => 'Вы покинули группу']);
        }

        if ($chat->type === 'private') {
            $chat->users()->updateExistingPivot($myId, ['deleted_at' => now()]);

            $isCompanionDeleted = $chat->users()
                ->where('users.id', '!=', $myId)
                ->wherePivotNotNull('deleted_at')
                ->exists();

            if ($isCompanionDeleted) {
                $chat->delete();
                return response()->json(['message' => 'Чат удален']);
            }
        }

        return response()->json(['message' => 'Вы покинули чат']);
    }
}
