<?php

namespace App\Http\Controllers;

use App\Events\LikesUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function index(Request $request, User $user, string $type, string $id)
    {
        $modelClass = Relation::getMorphedModel($type);
        $model = $modelClass::findOrFail($id);

        $likes = $model->likes()->with([
            'user' => fn($query) => $query->select('id', 'name'),
            'user.activeAvatar'
        ])->get();

        return response()->json([
            'data' => $likes
        ]);
    }

    public function toggle(Request $request, User $user, string $type, string $id)
    {
        $modelClass = Relation::getMorphedModel($type);
        $model = $modelClass::findOrFail($id);

        $like = $model->likes()->where('user_id', auth()->id())->first();

        if ($like) {
            $like->delete();
        } else {
            $model->likes()->create(['user_id' => auth()->id()]);
        }

        $like = $model->likes()->with([
            'user' => fn($query) => $query->select('id', 'name'),
            'user.activeAvatar'
        ])->get();

        broadcast(new LikesUpdate($like->toArray(), $type, $id))->toOthers();

        return response()->json([
            'data' => $like
        ]);
    }
}
