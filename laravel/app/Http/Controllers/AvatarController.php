<?php

namespace App\Http\Controllers;

use App\Models\Avatar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function index(User $user)
    {
        $avatars = $user->avatars->map(function ($avatar) {
            return [
                'id' => $avatar->id,
                'url' => $avatar->avatar_url,
                'is_active' => $avatar->is_active,
            ];
        });

        return response()->json([
            'data' => $avatars,
        ]);
    }

    public function store(User $user, Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image',
        ]);

        try {
            $user->avatars()->update(['is_active' => false]);

            $folder = date('Y/m/') . $user->id . '/avatars';
            $path = $request->file('avatar')->store($folder, 's3');

            $avatar = $user->avatars()->create([
                'path' => $path,
                'avatar_url' => Storage::disk('s3')->url($path),
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Аватар успешно загружен',
                'data' => [
                    'avatar' => $avatar->avatar_url,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка S3: ' . $e->getMessage()], 500);
        }
    }
}
