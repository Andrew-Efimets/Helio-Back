<?php

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
    Route::post('/user', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::get('/user/{id}', [UserController::class, 'show'])
        ->middleware('auth:sanctum');
    Route::patch('/user/{id}', [UserController::class, 'update'])
        ->middleware('auth:sanctum');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])
        ->middleware('auth:sanctum');
});


