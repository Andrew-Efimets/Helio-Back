<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendFileToS3 implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;

    public function __construct(
        public        $model,
        public string $tempPath,
        public string $finalPath
    )
    {
    }

    public function handle(): void
    {
        try {
            Log::info('start job');
            if (!Storage::disk('local')->exists($this->tempPath)) {
                return;
            }

            Log::info('local exist');

            $fileStream = Storage::disk('local')->readStream($this->tempPath);

            Log::info('$fileStream exist');

            Storage::disk('s3')->put($this->finalPath, $fileStream);

            Log::info('store in s3');

            $this->model->update([
                'path' => $this->finalPath,
                'video_url' => Storage::disk('s3')->url($this->finalPath),
            ]);

            Storage::disk('local')->delete($this->tempPath);


            broadcast(new \App\Events\VideoProcessed($this->model));
        } catch (\Exception $exception) {
            Log::error('Ошибка при загрузке в S3: ' . $exception->getMessage());
            Log::error($exception->getTraceAsString());
        }
    }
}
