<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create($validated);

            $user->profile()->create([
                'privacy' => [
                    'show_phone' => 'public',
                    'show_account' => 'public',
                    'show_photo' => 'public',
                    'show_video' => 'public',
                    'show_contacts' => 'public',
                ],
                'country' => null,
                'city' => null,
            ]);

            return $user;
        });


        SmsService::sendSms($user);

        return response()->json([
            'message' => 'Пользователь зарегистрирован. Введите код из СМС.',
            'phone' => $user->phone
        ]);
    }

    public function login(LoginRequest $request)
    {
        $request->validated();

        $phone = $request->phone;
        $password = $request->password;

        $user = User::where('phone', $phone)->first();

        if ($user && Hash::check($password, $user->password)) {

            SmsService::sendSms($user);

            return response()->json([
                'message' => 'Пользователь найден. Введите код из СМС.',
                'phone' => $user->phone
            ]);
        } else {
            return response()->json([
                'message' => 'Пользователь не найден'
            ], 404);
        }
    }

    public function verify(VerifyCodeRequest $request)
    {
        $request->validated();

        $phone = $request->phone;
        $inputCode = $request->code;

        $cachedCode = Redis::get("sms_code:{$phone}");

        if (!$cachedCode || $cachedCode !== $inputCode) {
            return response()->json(['message' => 'Неверный или просроченный код'], 422);
        }

        $user = User::where('phone', $phone)->firstOrFail();

        $user->update([
            'phone_verified_at' => now(),
        ]);

        Redis::del("sms_code:{$phone}");

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Вход выполнен успешно',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Выход выполнен успешно',
        ], 200);
    }
}
