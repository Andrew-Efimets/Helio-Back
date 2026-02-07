<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avatar extends Model
{
    protected $fillable = [
        'user_id',
        'path',
        'avatar_url',
        'is_active',
        ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
