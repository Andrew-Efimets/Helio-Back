<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:users',
            'password' => 'required',
        ]);

        $user = User::create($validated);

        $code = (string) rand(1000, 9999);

        Redis::setex("sms_code:{$user->phone}", 300, $code);

        Log::info("Код подтверждения для {$user->phone}: {$code}");

        return response()->json([
            'message' => 'Пользователь зарегистрирован. Введите код из СМС.',
            'phone'   => $user->phone
        ]);
    }

    public function login(Request $request)
    {
        $phone = $request->phone;
        $password = $request->password;

        $user = User::where('phone', $phone)->firstOrFail();

        if($user && Hash::check($password, $user->password)){
            $code = (string) rand(1000, 9999);

            Redis::setex("sms_code:{$user->phone}", 300, $code);

            Log::info("Код подтверждения для {$user->phone}: {$code}");
            return response()->json([
                'message' => 'Пользователь найден. Введите код из СМС.',
                'phone'   => $user->phone
            ]);
        }
        else {
            return response()->json([
                'message' => 'Пользователь не найден'
            ]);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'code'  => 'required|numeric',
        ]);

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
            'user'    => $user
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
