<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public static function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);

            $user->profile()->create([
                'privacy' => [
                    'show_phone' => 'public',
                    'show_account' => 'public',
                    'show_photo' => 'public',
                    'show_video' => 'public',
                    'show_contacts' => 'public',
                ],
                'country' => 'не указан',
                'city' => 'не указан',
            ]);

            return $user;
        });
    }
}
