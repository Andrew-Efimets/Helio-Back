<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'country',
        'city',
        'birthday',
        'privacy',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $attributes = [
        'privacy' => '{
        "show_phone":"public",
        "show_account":"public",
        "show_photo":"public",
        "show_video":"public",
        "show_contacts":"public"
        }'
    ];

    protected $casts = [
        'privacy' => 'array',
        'birthday' => 'date:Y-m-d',
    ];
}
