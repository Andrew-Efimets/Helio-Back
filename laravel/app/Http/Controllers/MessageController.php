<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Models\Chat;
use App\Models\Message;
use App\Traits\HasOwnerStatus;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use HasOwnerStatus;

    public function store(Request $request, Chat $chat)
    {
        $this->checkOwner($chat);

        $validated = $request->validate([
            'text' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
            'parent_content' => 'nullable|string',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $parentUserName = null;
        $parentUserAvatar = null;

        if ($parentId) {
            $parentMessage = Message::with('user.activeAvatar')
                ->find($parentId);
            if ($parentMessage && $parentMessage->user) {
                $parentUserName = $parentMessage->user->name;
                $parentUserAvatar = $parentMessage->user->activeAvatar?->avatar_url;
            }
        }

        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validated['text'],
            'parent_id' => $parentId,
            'parent_content' => $validated['parent_content'] ?? null,
            'parent_user_name' => $parentUserName,
            'parent_user_avatar' => $parentUserAvatar,
        ]);

        $message->load('user.activeAvatar');

        broadcast(new MessageCreated($message))->toOthers();

        return response()->json([
            'data' => $message,
        ], 201);
    }

    public function update(Request $request, Chat $chat, Message $message)
    {
        $this->checkOwner($message);

        $validated = $request->validate([
            'text' => 'required',
        ]);

        $message->update([
            'content' => $validated['text'],
        ]);

        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json([
            'data' => $message,
        ], 201);
    }

    public function destroy(Chat $chat, Message $message)
    {
        $this->checkOwner($message);
        $messageId = $message->id;
        $message->delete();
        broadcast(new MessageDeleted($chat->id, $messageId))->toOthers();
        return response()->json([
            'message' => 'Cообщение удалено',
        ]);
    }
}
