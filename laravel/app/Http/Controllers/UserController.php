<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserShortResource;
use App\Models\User;
use App\Traits\HasOwnerStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HasOwnerStatus;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $direction = in_array(strtolower($request->sort), ['asc', 'desc'])
            ? $request->sort
            : 'asc';

        $users = User::query()
            ->where('id', '!=', auth()->id())
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->city, function ($query, $city) {
                $query->whereHas('profile', function ($q) use ($city) {
                    $q->whereRaw("MATCH(city) AGAINST(? IN BOOLEAN MODE)", [$city . '*']);
                });
            })
            ->when($request->country, function ($query, $country) {
                $query->whereHas('profile', function ($q) use ($country) {
                    $q->whereRaw("MATCH(country) AGAINST(? IN BOOLEAN MODE)", [$country . '*']);
                });
            })
            ->when($request->age_from || $request->age_to, function ($query) use ($request) {
                $query->whereHas('profile', function ($q) use ($request) {
                    $from = $request->age_from ?? 0;
                    $to = $request->age_to ?? 100;

                    $dateStart = Carbon::now()->subYears($to + 1)->addDay()->format('Y-m-d');
                    $dateEnd = Carbon::now()->subYears($from)->format('Y-m-d');

                    $q->whereBetween('birthday', [$dateStart, $dateEnd]);
                });
            })
            ->with(['activeAvatar', 'profile', 'contactPivot'])
            ->withCount(['photos', 'videos', 'contacts'])
            ->orderBy('name', $direction)
            ->paginate(20);

        return UserShortResource::collection($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['avatars', 'profile'])
            ->loadCount([
                'photos',
                'videos',
                'contacts',
                'pending_contacts',
//                'unread_messages',
            ]);

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

            $user->fresh()->load(['avatars', 'profile'])
                ->loadCount(['photos', 'videos', 'contacts']);

            return response()->json([
                'message' => 'Данные успешно обновлены',
                'data' => new UserResource($user)
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
        $this->checkOwner($user);

        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $user->delete();

        return response()->json([
            'message' => 'Пользователь удалён'
        ]);
    }
}
