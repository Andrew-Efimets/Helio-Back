<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function index(User $user)
    {
        return response()->json([
            'message' => 'Запрос сработал',
            'data' => $user,
        ]);
    }

    public function store(VideoRequest $request, User $user)
    {
        try {
            $folder = date('Y/m/') . $user->id . '/videos';
            $path = $request->file('video')->store($folder, 's3');

            $video = $user->videos()->create([
                'path' => $path,
                'video_url' => Storage::disk('s3')->url($path),

            ]);

            return response()->json([
                'message' => 'Видео успешно загружено',
                'data' => [
                    'video' => $video->video_url,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка S3: ' . $e->getMessage()], 500);
        }
    }

    public function show(Video $video)
    {

    }

    public function destroy(Video $video)
    {

    }
}
