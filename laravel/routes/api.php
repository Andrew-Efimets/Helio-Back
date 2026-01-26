<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

//Route::get('test', function () {
//    return response()->json([
//        'message' => 'Your test'
//    ]);
//});

Route::get('/login', function () {
    return 'Unauthorize';
})->name('login');

Route::prefix('/v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:60,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:60,1');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::get('/user/{id}', [UserController::class, 'show'])
        ->middleware('auth:sanctum');
    Route::patch('/user/{id}', [UserController::class, 'update'])
        ->middleware('auth:sanctum');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])
        ->middleware('auth:sanctum');
    Route::get('/profile/{user}/avatars', [AvatarController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::post('/profile/{user}/avatar', [AvatarController::class, 'store'])
        ->middleware('auth:sanctum');
    Route::patch('/profile/{user}/avatar/{avatar}', [AvatarController::class, 'update'])
        ->middleware('auth:sanctum');
    Route::delete('/profile/{user}/avatar/{avatar}', [AvatarController::class, 'destroy'])
        ->middleware('auth:sanctum');
});


