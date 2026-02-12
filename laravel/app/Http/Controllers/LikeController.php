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

        return response()->json([
            'data' => $model->likes()->with('user.activeAvatar')->get()
        ]);
    }

    public function toggle(Request $request, User $user, string $type, string $id)
    {
        $modelClass = Relation::getMorphedModel($type);
        $model = $modelClass::findOrFail($id);

        $like = $model->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
        } else {
            $model->likes()->create(['user_id' => $user->id]);
        }

        $like = $model->likes()->with('user.activeAvatar')->get();

        broadcast(new LikesUpdate($like->toArray(), $type, $id))->toOthers();

        return response()->json([
            'data' => $like
        ]);
    }
}
