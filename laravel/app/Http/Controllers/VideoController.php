<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoRequest;
use App\Jobs\SendFileToS3;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $finalPath = date('Y/m/') . $user->id . '/videos/' . $file->hashName();

            $video = $user->videos()->create([
                'path' => 'processing...',
                'video_url' => null,
            ]);

            Log::info('Данные перед отправкой в S3:', [
                'temp' => $tempPath,
                'final' => $finalPath,
                'video' => $video->toArray()
            ]);

            SendFileToS3::dispatch($video, $tempPath, $finalPath);

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
