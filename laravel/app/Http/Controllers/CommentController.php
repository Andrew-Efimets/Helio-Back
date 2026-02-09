<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Models\Photo;
use App\Models\Post;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, User $user, string $type, string $id)
    {
        $models = [
            'video' => Video::class,
            'photo' => Photo::class,
            'post'  => Post::class,
        ];

        if (!array_key_exists($type, $models)) {
            abort(404, "Unknown commentable type");
        }

        $modelClass = $models[$type];
        $commentable = $modelClass::where('user_id', $user->id)->findOrFail($id);
        $comments = $commentable->comments()
            ->with('user.activeAvatar')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Все комментарии ' . $type . $id,
            'data' => $comments,
        ], 200);
    }

    public function store(Request $request, User $user, string $type, string $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $models = [
            'video' => Video::class,
            'photo' => Photo::class,
            'post'  => Post::class,
        ];

        if (!array_key_exists($type, $models)) {
            abort(404, "Неизвестный объект для комментария");
        }

        $modelClass = $models[$type];

        $commentable = $modelClass::where('user_id', $user->id)->findOrFail($id);

        $comment = $commentable->comments()->create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
        ]);

        broadcast(new CommentCreated($comment))->toOthers();

        return response()->json([
            'data' => $comment->load('user.activeAvatar'),
        ], 201);
    }
}
