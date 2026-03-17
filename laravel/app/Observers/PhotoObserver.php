<?php

namespace App\Observers;

use App\Jobs\DeleteS3File;
use App\Models\Photo;

class PhotoObserver
{
    public function deleted(Photo $photo): void
    {
        if ($photo->path) {
            DeleteS3File::dispatch([$photo->path]);
        }
    }
}
