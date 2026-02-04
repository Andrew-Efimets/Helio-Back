<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteS3File implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(
        protected array $pathsToDelete
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->pathsToDelete as $path) {
            if (empty($path)) continue;

            Storage::disk('s3')->delete($path);
        }
    }
}
