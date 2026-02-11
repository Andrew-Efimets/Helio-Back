<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'phone_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function avatars(): HasMany
    {
        return $this->hasMany(Avatar::class);
    }

    public function activeAvatar(): HasOne
    {
        return $this->hasOne(Avatar::class)->where('is_active', 1);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'contacts',
            'user_id',
            'contact_id'
        )
            ->withPivot('status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function pending_contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'contacts',
            'contact_id',
            'user_id'
        )
            ->wherePivot('status', 'pending');
    }

    public function addedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'contacts',
            'contact_id',
            'user_id'
        )->withTimestamps();
    }

    public function isContactWith($targetId): bool
    {
        return $this->contacts()->where('contact_id', $targetId)->exists();
    }
}
