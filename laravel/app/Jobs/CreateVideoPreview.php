<?php

namespace App\Jobs;

use App\Models\Video;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateVideoPreview implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 30;
    public $timeout = 600;

    public function __construct(
        public Video $video,
        public string $tempPath)
    {}

    public function handle(): void
    {
        $localPath = storage_path('app/private/' . $this->tempPath);
        $localPreviewPath = storage_path('app/private/temp/preview_' . bin2hex(random_bytes(8)) . '.mp4');

        try {
            if (!file_exists($localPath)) {
                Log::error("Исходный файл для превью не найден: $localPath");
                return;
            }

            $ffmpeg = FFMpeg::create();
            $videoFile = $ffmpeg->open($localPath);

            $format = new X264();
            $format->setAdditionalParameters([
                '-an',
                '-t', '7',
                '-vf', 'scale=480:-2',
                '-vcodec', 'libx264',
                '-pix_fmt', 'yuv420p',
            ]);

            $videoFile->save($format, $localPreviewPath);

            $s3PreviewPath = dirname($this->video->path) . '/previews/' . basename($localPreviewPath);

            Storage::disk('s3')->put($s3PreviewPath, fopen($localPreviewPath, 'r+'));

            $this->video->update([
                'preview_url' => Storage::disk('s3')->url($s3PreviewPath),
            ]);

        } catch (\Exception $exception) {
            Log::error('Ошибка конвертации: ' . $exception->getMessage());
        } finally {
            if (file_exists($localPreviewPath)) {
                unlink($localPreviewPath);
            }
        }
    }
}
