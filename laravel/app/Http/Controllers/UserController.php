<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['avatars', 'profile']);

        return response()->json([
            'message' => 'Переданы данные пользователя',
            'data' => new UserResource($user)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $user) {
                $user->update($validated);

                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $validated
                );
            });

            return response()->json([
                'message' => 'Данные успешно обновлены',
                'data' => $user->load('profile')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при сохранении данных',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
