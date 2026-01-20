<?php
//
//namespace App\Models;
//
//use Illuminate\Database\Eloquent\Model;
//
//class Message extends Model
//{
//    //
//}


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photos extends Model
{
    protected $fillable = [
        'filename',
        'user_id',
    ];

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute()
    {
        if (!$this->filename) {
            return null;
        }

        return Storage::disk('s3')->url($this->filename);
    }
}
