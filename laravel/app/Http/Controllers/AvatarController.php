<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvatarRequest;
use App\Http\Resources\AvatarResource;
use App\Models\Avatar;
use App\Models\User;
use App\Traits\HasOwnerStatus;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    use HasOwnerStatus;

    public function index(User $user)
    {
        return response()->json([
            'message' => 'Успех',
            'data' => AvatarResource::collection($user->avatars),
        ]);
    }

    public function store(User $user, AvatarRequest $request)
    {
        try {
            $user->avatars()->update(['is_active' => false]);

            $folder = $user->created_at->format('Y/m/') . $user->id . '/avatars';
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

    public function update(User $user, Avatar $avatar)
    {
        $this->checkOwner($avatar);

        try {
            $user->avatars()->update(['is_active' => false]);

            $avatar->update(['is_active' => true]);

            return response()->json([
                'message' => 'Аватар успешно загружен',
                'data' => [
                    'avatar' => $avatar->avatar_url,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка БД: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(User $user, Avatar $avatar)
    {
        $this->checkOwner($avatar);

        try {
            if ($avatar->is_active) {
                $next = $user->avatars()
                    ->where('id', '!=', $avatar->id)
                    ->latest()
                    ->first();
                if ($next) {
                    $next->update(['is_active' => true]);
                }
            }

            if ($avatar->path) {
                Storage::disk('s3')->delete($avatar->path);
            }
            $avatar->delete();

            return response()->json([
                'message' => 'Аватар удалён',
                'data' => [
                    'avatar' => $user->fresh('avatars')
                        ->avatars
                        ->where('is_active', true)
                        ->first()?->avatar_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
}
