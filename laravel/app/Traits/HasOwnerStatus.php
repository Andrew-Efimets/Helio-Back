<?php

namespace App\Traits;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasOwnerStatus
{
    protected function checkOwner(Model $model): void
    {
        $checkId = match (true) {
            $model instanceof User => $model->id,
            $model instanceof Chat => $model->users()
                ->where('users.id', auth()->id())
                ->value('users.id'),
            default => $model->user_id,
        };

        if ((int) $checkId !== (int) auth()->id()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'У вас нет прав для выполнения этого действия'
                ], 403)
            );
        }
    }
}
