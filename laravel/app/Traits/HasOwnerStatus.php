<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasOwnerStatus
{
    protected function checkOwner(Model $model): void
    {
        if ($model->user_id !== auth()->id()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'У вас нет прав для выполнения этого действия'
                ], 403)
            );
        }
    }
}
