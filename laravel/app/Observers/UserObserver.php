<?php

namespace App\Observers;

use App\Jobs\DeleteUserS3Folder;
use App\Models\User;

class UserObserver
{
    public function deleted(User $user)
    {
        $folder = $user->created_at->format('Y/m/') . $user->id;

        DeleteUserS3Folder::dispatch($folder);
    }
}
