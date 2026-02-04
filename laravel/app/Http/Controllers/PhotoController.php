<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoRequest;
use App\Jobs\SendPhotoToS3;
use App\Models\Photo;
use App\Models\User;
use App\Traits\HasOwnerStatus;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    use HasOwnerStatus;

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
            $file = $request->file('photo');
            $tempPath = $file->store('temp', 'local');

            $photo = $user->photos()->create([
                'path' => 'processing...',
                'photo_url' => null,
            ]);

            SendPhotoToS3::dispatch($photo, $tempPath);

            return response()->json([
                'message' => 'Фотография принята в обработку',
                'data' => ['photo' => $photo],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

    public function show(Photo $photo)
    {

    }

    public function destroy(User $user, Photo $photo)
    {
        $this->checkOwner($photo);

        $photo->delete();

        return response()->json([
            'message' => 'Фотография успешно удалена'
        ], 200);

    }
}
