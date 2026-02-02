<?php

namespace App\Jobs;

use App\Events\VideoProcessed;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendFileToS3 implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;

    public function __construct(
        public        $model,
        public string $tempPath,
        public string $finalPath,
        public string $thumbnailFinalPath,
    )
    {
    }

    public function handle(): void
    {
        try {
            if (!Storage::disk('local')->exists($this->tempPath)) {
                return;
            }

            $localPath = storage_path('app/private/' . $this->tempPath);
            $thumbnailName = 'thumb_' . bin2hex(random_bytes(8)) . '.jpg';
            $tempThumbPath = 'temp/' . $thumbnailName;
            $absoluteThumbPath = storage_path('app/private/temp/' . $thumbnailName);
            $thumbnailFinalPath = $this->thumbnailFinalPath . $thumbnailName;

            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open($localPath);
            $video->frame(TimeCode::fromSeconds(2))->save($absoluteThumbPath);


            $fileStream = Storage::disk('local')->readStream($this->tempPath);
            $thumbStream = Storage::disk('local')->readStream($tempThumbPath);

            Storage::disk('s3')->put($this->finalPath, $fileStream);
            Storage::disk('s3')->put($thumbnailFinalPath, $thumbStream);

            $this->model->update([
                'path' => $this->finalPath,
                'video_url' => Storage::disk('s3')->url($this->finalPath),
                'thumbnail_url' => Storage::disk('s3')->url($thumbnailFinalPath),
            ]);

            Storage::disk('local')->delete($this->tempPath);
            if (Storage::disk('local')->exists($tempThumbPath)) {
                Storage::disk('local')->delete($tempThumbPath);
            }


            broadcast(new VideoProcessed($this->model));
        } catch (\Exception $exception) {
            Log::error('Ошибка S3/FFmpeg: ' . $exception->getMessage());
        }
    }
}
