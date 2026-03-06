<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeleteGroupAvatar implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ?string $avatar) {}

    public function handle(): void
    {
        if (!$this->avatar) return;

        $fullPath = parse_url($this->avatar, PHP_URL_PATH);
        $cleanPath = Str::after($fullPath, '/heliophone/');

        $directoryPath = Str::beforeLast($cleanPath, '/avatar');

        if (Storage::disk('s3')->exists($directoryPath)) {
            Storage::disk('s3')->deleteDirectory($directoryPath);
        }
    }
}
