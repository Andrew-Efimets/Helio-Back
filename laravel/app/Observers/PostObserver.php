<?php

namespace App\Observers;

use App\Jobs\DeletePostImageS3;
use App\Models\Post;

class PostObserver
{
    public function deleted(Post $post): void
    {
        if ($post->path) {
            DeletePostImageS3::dispatch([$post->path]);
        }
    }
}
