<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoRequest;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index(User $user)
    {
        $photos = $user->photos()->orderBy('created_at', 'desc')->get();
        return response()->json([
            'message' => 'Запрос сработал',
            'data' => $photos,
        ]);
    }

    public function store(PhotoRequest $request, User $user)
    {
        try {
            $folder = date('Y/m/') . $user->id . '/photos';
            $path = $request->file('photo')->store($folder, 's3');

            $photo = $user->photos()->create([
                'path' => $path,
                'photo_url' => Storage::disk('s3')->url($path),
            ]);

            return response()->json([
                'message' => 'Фотография успешно загружена',
                'data' => [
                    'photo' => $photo,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка S3: ' . $e->getMessage()], 500);
        }
    }

    public function show(Photo $photo)
    {

    }

    public function destroy(Photo $photo)
    {

    }
}
