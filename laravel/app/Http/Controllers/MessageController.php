<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Models\Chat;
use App\Models\User;
use App\Traits\HasOwnerStatus;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use HasOwnerStatus;

    public function store(Request $request, User $user, Chat $chat)
    {
        $this->checkOwner($chat);

        $validatedData = $request->validate([
            'text' => 'required',
        ]);

        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validatedData['text'],
        ]);

        broadcast(new MessageCreated($message))->toOthers();

        return response()->json([
            'data' => $message,
        ], 201);

    }

    public function update(Request $request, User $user, Chat $chat)
    {

    }

    public function destroy(User $user, Chat $chat)
    {

    }
}
