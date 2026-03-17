<?php

namespace App\Observers;

use App\Jobs\DeleteGroupAvatar;
use App\Models\Chat;

class ChatObserver
{
    public function deleted(Chat $chat): void
    {
        if ($chat->avatar) {
            DeleteGroupAvatar::dispatch($chat->avatar);
        }
    }
}
