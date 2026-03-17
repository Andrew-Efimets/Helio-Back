<?php

namespace App\Jobs;

use App\Events\PostCreated;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class SendPostImageToS3 implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Post $post, public string $tempPath)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->post->user;
        $folder = $user->created_at->format('Y/m/') . $user->id . '/posts';
        $fileName = basename($this->tempPath);
        $finalS3Path = $folder . '/' . $fileName;

        $fileStream = Storage::disk('local')->readStream($this->tempPath);
        Storage::disk('s3')->put($finalS3Path, $fileStream);

        $this->post->update([
            'path' => $finalS3Path,
            'image_url' => Storage::disk('s3')->url($finalS3Path),
        ]);

        Storage::disk('local')->delete($this->tempPath);

        broadcast(new PostCreated($this->post));
    }
}
