<?php

namespace App\Http\Controllers;

use App\Models\User;

class ContactController extends Controller
{
    public function toggle(User $user)
    {
        if (auth()->user()->id === $user->id) {
            return response()->json(['message' => 'Нельзя добавить самого себя'], 400);
        }

        $result = auth()->user()->contacts()->toggle($user->id);

        $isAdded = count($result['attached']) > 0;

        return response()->json([
            'message' => $isAdded ? 'Контакт добавлен' : 'Контакт удален',
            'is_contact' => $isAdded,
        ]);
    }
}
