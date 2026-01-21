<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SmsService
{
    public static function sendSms(User $user)
    {
        $code = (string) rand(1000, 9999);

        Redis::setex("sms_code:{$user->phone}", 300, $code);

        Log::info("Код подтверждения для {$user->phone}: {$code}");

    }
}
