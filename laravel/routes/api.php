<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PostController;
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
            Route::post('/contact/accept', [ContactController::class, 'accept']);

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

            Route::get('/posts', [PostController::class, 'index'])
                ->middleware('privacy:show_account');
            Route::post('/post', [PostController::class, 'store']);
            Route::patch('/post/{post}', [PostController::class, 'update']);
            Route::delete('/post/{post}', [PostController::class, 'destroy']);
        });

        Route::prefix('/chats')->group(function () {
            Route::get('/', [ChatController::class, 'index']);
            Route::get('/chat/{chat}', [ChatController::class, 'show']);
            Route::post('/chat', [ChatController::class, 'store']);
            Route::post('/group', [ChatController::class, 'storeGroup']);
            Route::patch('/chat/{chat}', [ChatController::class, 'update']);
            Route::delete('/chat/{chat}', [ChatController::class, 'destroy']);
            Route::post('/chat/{chat}/read', [ChatController::class, 'markRead']);
            Route::post('/chat/{chat}/leave', [ChatController::class, 'leaveChat']);
            Route::post('/chat/{chat}/members/{user}', [ChatController::class, 'addMember']);
            Route::delete('/chat/{chat}/members/{user}', [ChatController::class, 'deleteMember']);
            Route::post('/chat/{chat}/messages', [MessageController::class, 'store']);
            Route::patch('/chat/{chat}/messages/{message}', [MessageController::class, 'update']);
            Route::delete('/chat/{chat}/messages/{message}', [MessageController::class, 'destroy']);
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
                Route::get('/likes', [LikeController::class, 'index']);
                Route::post('/likes', [LikeController::class, 'toggle']);
            });

        })->where('type', 'video|photo|post');
    });
});




