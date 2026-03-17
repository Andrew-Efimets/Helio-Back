<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\User;
use App\Services\SmsService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = UserService::createUser($validated);

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

        if ($request->boolean('reset')) {
            return response()->json([
                'message' => 'Код подтвержден, введите новый пароль',
                'data' => ['id' => $user->id]
            ]);
        }

        $user->update(['phone_verified_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        Redis::del("sms_code:{$phone}");

        return response()->json([
            'message' => 'Вход выполнен успешно',
            'data' => ['id' => $user->id]
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

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:10',
        ]);

        $phone = $request->phone;

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Пользователь с таким номером не найден'
            ], 404);
        }

        SmsService::sendSms($user);

        return response()->json([
            'message' => 'Код восстановления отправлен на ваш номер',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
            'code' => 'required|string|size:4',
        ]);

        $phone = $request->phone;
        $inputCode = $request->code;

        $cachedCode = Redis::get("sms_code:{$phone}");

        if (!$cachedCode || $cachedCode !== $inputCode) {
            return response()->json([
                'message' => 'Неверный или просроченный код подтверждения'
            ], 422);
        }

        $user = User::where('phone', $phone)->firstOrFail();

        $user->update([
            'password' => Hash::make($request->password),
            'phone_verified_at' => now(),
        ]);

        Redis::del("sms_code:{$phone}");

        return response()->json([
            'message' => 'Пароль успешно изменен. Теперь вы можете войти.'
        ]);
    }
}
