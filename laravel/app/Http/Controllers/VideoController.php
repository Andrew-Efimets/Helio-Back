<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Jobs\ConvertVideoForWeb;
use App\Jobs\CreateVideoPreview;
use App\Jobs\SendVideoToS3;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Bus;

class VideoController extends Controller
{
    public function index(User $user)
    {
        return response()->json([
            'message' => 'Запрос сработал',
            'data' => $user->videos()->get(),
        ]);
    }

    public function store(VideoRequest $request, User $user)
    {
        try {
            $file = $request->file('video');

            $tempPath = $file->store('temp', 'local');
            $finalPath = $user->created_at->format('Y/m/') . $user->id . '/videos/' . $file->hashName();
            $thumbnailFinalPath = $user->created_at
                    ->format('Y/m/'). $user->id . '/thumbnails/';

            $video = $user->videos()->create([
                'path' => 'processing...',
                'video_url' => null,
                'thumbnail_url' => null,
            ]);

            Bus::chain([
                new SendVideoToS3($video, $tempPath, $finalPath, $thumbnailFinalPath),
                new CreateVideoPreview($video, $tempPath),
                new ConvertVideoForWeb($video, $tempPath),
            ])->dispatch();



            return response()->json([
                'message' => 'Видео принято в обработку и скоро появится',
                'data' => ['video' => $video]
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
