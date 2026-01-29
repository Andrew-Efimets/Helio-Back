<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteUserS3Folder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected string $folderPath;
    public function __construct($folderPath)
    {
        $this->folderPath = $folderPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk('s3')->deleteDirectory($this->folderPath);
    }
}
