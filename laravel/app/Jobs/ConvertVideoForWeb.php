<?php

namespace App\Jobs;

use App\Events\VideoProcessed;
use App\Models\Video;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ConvertVideoForWeb implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;
    public $tries = 1;

    public function __construct(public Video $video, public string $tempPath) {}

    public function handle(): void
    {
        $localPath = storage_path('app/private/' . $this->tempPath);
        $webName = 'web_' . bin2hex(random_bytes(8)) . '.mp4';
        $localWebPath = storage_path('app/private/temp/' . $webName);

        try {
            if (!file_exists($localPath)) return;

            $ffmpeg = FFMpeg::create([
                'timeout'          => 3600,
                'ffmpeg.threads'   => 4,
            ]);
            $videoFile = $ffmpeg->open($localPath);

            $format = new X264('aac', 'libx264');
            $format->setPasses(1);
            $format->setAdditionalParameters([
                '-vf', 'scale=1280:-2',
                '-pix_fmt', 'yuv420p',
                '-preset', 'veryfast',
                '-crf', '23'
            ]);

            $videoFile->save($format, $localWebPath);

            $s3WebPath = dirname($this->video->path) . '/' . $webName;
            Storage::disk('s3')->put($s3WebPath, fopen($localWebPath, 'r+'));

            $this->video->update([
                'video_url' => Storage::disk('s3')->url($s3WebPath),
            ]);
            broadcast(new VideoProcessed($this->video));

        } finally {
            if (Storage::disk('local')->exists($this->tempPath)) {
                Storage::disk('local')->delete($this->tempPath);
            }
            if (file_exists($localWebPath)) {
                unlink($localWebPath);
            }
        }
    }
}
