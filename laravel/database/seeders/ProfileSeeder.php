<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usersWithoutProfile = User::doesntHave('profile')->get();

        foreach ($usersWithoutProfile as $user) {
            Profile::create([
                'user_id' => $user->id,
                'privacy' => [
                    'show_phone' => 'public',
                    'show_account' => 'public',
                    'show_photo' => 'public',
                    'show_video' => 'public',
                    'show_contacts' => 'public',
                ],
                'country' => null,
                'city' => null,
                'birthday' => null,
            ]);
        }
    }
}
