<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_user')
            ->withPivot(['role', 'status', 'deleted_at'])
            ->withTimestamps();
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
            'contact_id')
            ->withPivot('status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->where(function ($query) {
                $query->where('contacts.user_id', $this->id)
                    ->orWhere('contacts.contact_id', $this->id);
            });
    }

    public function pending_contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'contacts',
            'contact_id',
            'user_id'
        )
            ->withPivot('status')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }

    public function scopeWithSymmetricContactsCount($query)
    {
        return $query->addSelect(['contacts_count' => function ($q) {
            $q->selectRaw('count(*)')
                ->from('contacts')
                ->where('status', 'accepted')
                ->where(function ($inner) {
                    $inner->whereColumn('contacts.user_id', 'users.id')
                        ->orWhereColumn('contacts.contact_id', 'users.id');
                });
        }]);
    }

    public function allContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'contacts',
            'user_id',
            'contact_id'
        )
            ->withPivot('status')
            ->withTimestamps();
    }


    public function getContactStatus()
    {
        $authId = auth()->id();
        if (!$authId) return null;

        return Contact::where(function ($q) use ($authId) {
            $q->where('user_id', $authId)->where('contact_id', $this->id);
        })
            ->orWhere(function ($q) use ($authId) {
                $q->where('user_id', $this->id)->where('contact_id', $authId);
            })
            ->first();
    }

    public function getContactStatusFor(User $contact)
    {
        return DB::table('contacts')
            ->where(function ($q) use ($contact) {
                $q->where('user_id', $this->id)->where('contact_id', $contact->id);
            })
            ->orWhere(function ($q) use ($contact) {
                $q->where('user_id', $contact->id)->where('contact_id', $this->id);
            })
            ->first();
    }

    public function scopeWithBaseData($query)
    {
        return $query->with(['activeAvatar', 'profile'])
            ->withSymmetricContactsCount()
            ->withCount(['photos', 'videos']);
    }

    public function scopeFiltered($query, $request)
    {
        return $query->when($request->search, fn($q, $search) => $q
            ->where('name', 'like', "%{$search}%"))
            ->when($request->city, fn($q, $city) =>
            $q->whereHas('profile', fn($inner) => $inner
                ->whereRaw(
                    "MATCH(city) AGAINST(? IN BOOLEAN MODE)",
                    [$city . '*']))
            )
            ->when($request->country, fn($q, $country) =>
            $q
                ->whereHas('profile', fn($inner) => $inner
                    ->whereRaw(
                        "MATCH(country) AGAINST(? IN BOOLEAN MODE)",
                        [$country . '*']))
            )
            ->when($request->age_from || $request->age_to, function ($q) use ($request) {
                $q->whereHas('profile', function ($inner) use ($request) {
                    $from = $request->age_from ?? 0;
                    $to = $request->age_to ?? 100;
                    $inner->whereBetween('birthday', [
                        now()->subYears($to + 1)->addDay()->format('Y-m-d'),
                        now()->subYears($from)->format('Y-m-d')
                    ]);
                });
            });
    }

    public function scopeWhereInContacts($query, $user, $status)
    {
        $contactIds = DB::table('contacts')
            ->where('status', $status)
            ->where(fn($q) => $q
                ->where('user_id', $user->id)
                ->orWhere('contact_id', $user->id))
            ->get()
            ->map(fn($row) => $row->user_id == $user->id ? $row->contact_id : $row->user_id)
            ->unique();

        return $query->whereIn('id', $contactIds);
    }
}
