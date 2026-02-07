<?php

namespace App\Jobs;

use App\Events\PhotoProcessed;
use App\Models\Photo;
use App\Observers\PhotoObserver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class SendPhotoToS3 implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Photo $photo, public string $tempPath)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->photo->user;
        $folder = $user->created_at->format('Y/m/') . $user->id . '/photos';
        $fileName = basename($this->tempPath);
        $finalS3Path = $folder . '/' . $fileName;

        $fileStream = Storage::disk('local')->readStream($this->tempPath);
        Storage::disk('s3')->put($finalS3Path, $fileStream);

        $this->photo->update([
            'path' => $finalS3Path,
            'photo_url' => Storage::disk('s3')->url($finalS3Path),
        ]);

        Storage::disk('local')->delete($this->tempPath);

        broadcast(new PhotoProcessed($this->photo));
    }
}
