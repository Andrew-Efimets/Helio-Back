<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, User $user, string $type, string $id)
    {
        $modelClass = Relation::getMorphedModel($type);
        $commentable = $modelClass::where('user_id', $user->id)->findOrFail($id);

        $comments = $commentable->comments()
            ->with(['user.activeAvatar'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $comments->load(['user.activeAvatar']),
        ], 200);
    }

    public function store(Request $request, User $user, string $type, string $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $modelClass = Relation::getMorphedModel($type);

        if (!$modelClass) {
            abort(404, "Неизвестный тип объекта");
        }

        $commentable = $modelClass::where('user_id', $user->id)->findOrFail($id);

        $comment = $commentable->comments()->create([
            'content'   => $validated['content'],
            'user_id'   => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load(['user.activeAvatar', 'parent.user']);

        broadcast(new CommentCreated($comment))->toOthers();

        return response()->json([
            'data' => $comment->load(['user.activeAvatar', 'parent.user']),
        ], 201);
    }
}
