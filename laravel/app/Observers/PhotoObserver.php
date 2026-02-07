<?php

namespace App\Observers;

use App\Jobs\DeleteS3File;
use App\Models\Photo;

class PhotoObserver
{
    /**
     * Handle the Photo "created" event.
     */
    public function created(Photo $photo): void
    {
        //
    }

    /**
     * Handle the Photo "updated" event.
     */
    public function updated(Photo $photo): void
    {
        //
    }

    /**
     * Handle the Photo "deleted" event.
     */
    public function deleted(Photo $photo): void
    {
        if ($photo->path) {
            DeleteS3File::dispatch([$photo->path]);
        }
    }

    /**
     * Handle the Photo "restored" event.
     */
    public function restored(Photo $photo): void
    {
        //
    }

    /**
     * Handle the Photo "force deleted" event.
     */
    public function forceDeleted(Photo $photo): void
    {
        //
    }
}
