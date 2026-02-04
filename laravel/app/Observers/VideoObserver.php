<?php

namespace App\Observers;

use App\Jobs\DeleteS3File;
use App\Models\Video;

class VideoObserver
{
    public function deleted(Video $video): void
    {
        $preparePath = function($url) {
            if (!$url) return null;
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');
            return str_replace('heliophone/', '', $path);
        };

        $pathsToDelete = array_filter([
            $video->path,
            $preparePath($video->video_url),
            $preparePath($video->thumbnail_url),
            $preparePath($video->preview_url),
        ]);

        if (!empty($pathsToDelete)) {
            DeleteS3File::dispatch(array_values($pathsToDelete));
        }
    }

}
