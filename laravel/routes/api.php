<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/login', function () {
    return 'Unauthorize';
})->name('login');

Route::prefix('/v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:60,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/users', [UserController::class, 'index']);

        Route::prefix('/user/{user}')->group(function () {
            Route::get('/', [UserController::class, 'show']);
            Route::patch('/', [UserController::class, 'update']);
            Route::delete('/', [UserController::class, 'destroy']);

            Route::get('/contacts', [ContactController::class, 'index'])
                ->middleware('privacy:show_contacts');
            Route::post('/contact', [ContactController::class, 'toggle']);

            Route::get('/photos', [PhotoController::class, 'index'])
                ->middleware('privacy:show_photo');
            Route::get('photo/{photo}', [PhotoController::class, 'show'])
                ->middleware('privacy:show_photo');
            Route::post('/photo', [PhotoController::class, 'store']);
            Route::delete('/photo/{photo}', [PhotoController::class, 'destroy']);

            Route::get('/videos', [VideoController::class, 'index'])
                ->middleware('privacy:show_video');
            Route::get('/video/{video}', [VideoController::class, 'show'])
                ->middleware('privacy:show_video');
            Route::post('/video', [VideoController::class, 'store']);
            Route::delete('/video/{video}', [VideoController::class, 'destroy']);

            Route::get('/chats', [ChatController::class, 'index'])
                ->middleware('privacy:show_chat');
            Route::get('/chat/{chat}', [ChatController::class, 'show'])
                ->middleware('privacy:show_chat');
            Route::post('/chat', [ChatController::class, 'store']);
            Route::patch('/chat', [ChatController::class, 'update']);
            Route::delete('/chat', [ChatController::class, 'destroy']);
        });

        Route::prefix('/profile/{user}')->group(function () {
            Route::get('/avatars', [AvatarController::class, 'index']);
            Route::post('/avatar', [AvatarController::class, 'store']);
            Route::patch('/avatar/{avatar}', [AvatarController::class, 'update']);
            Route::delete('/avatar/{avatar}', [AvatarController::class, 'destroy']);
        });

        Route::prefix('/user/{user}/{type}/{id}')->group(function () {

            Route::middleware('media_privacy')->group(function () {
                Route::get('/comments', [CommentController::class, 'index']);
                Route::post('/comments', [CommentController::class, 'store']);
            });

        })->where('type', 'video|photo|post');
    });
});




