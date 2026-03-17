<?php

namespace App\Http\Controllers;


use App\Events\ContactDeleted;
use App\Events\ContactRequestAccepted;
use App\Events\ContactRequestSent;
use App\Http\Resources\UserShortResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function index(Request $request, User $user)
    {
        $users = User::query()
            ->whereInContacts(
                $user,
                $request->query('contact_status', 'accepted')
            )
            ->withBaseData()
            ->orderBy('name', $request->query('sort', 'asc'))
            ->get();

        return UserShortResource::collection($users);
    }

    public function toggle(User $user)
    {
        /** @var \App\Models\User $me */
        $me = auth()->user();
        if ($me->id === $user->id) {
            return response()->json(['message' => 'Нельзя добавить самого себя'], 400);
        }

        $pivot = DB::table('contacts')
            ->where(fn($q) => $q->where('user_id', $me->id)->where('contact_id', $user->id))
            ->orWhere(fn($q) => $q->where('user_id', $user->id)->where('contact_id', $me->id))
            ->first();

        if ($pivot) {
            $status = $pivot->status;

            $isInitiator = (int)$pivot->user_id === (int)$me->id;

            DB::table('contacts')->where('id', $pivot->id)->delete();

            broadcast(new ContactDeleted($me, $user->id, $status, $isInitiator));

            return response()->json([
                'message' => $status === 'accepted'
                    ? 'Контакт удалён'
                    : ($isInitiator ? 'Запрос отменён' : 'Запрос отклонен'),
                'contact_status' => null,
                'contacts_count' => $this->getSymmetricCount($user, 'accepted'),
                'pending_contacts_count' => $this
                    ->getSymmetricCount($me, 'pending', 'receiver')
            ]);
        }

        $me->allContacts()->attach($user->id, ['status' => 'pending']);

        broadcast(new ContactRequestSent($me, $user->id));

        return response()->json([
            'message' => 'Запрос отправлен',
            'contact_status' => ['type' => 'pending', 'is_sender' => false],
            'contacts_count' => $this->getSymmetricCount($user, 'accepted'),
            'pending_contacts_count' => $this
                ->getSymmetricCount($me, 'pending', 'receiver')
        ]);
    }

    public function accept(User $user)
    {
        /** @var \App\Models\User $me */
        $me = auth()->user();

        $hasRequest = DB::table('contacts')
            ->where('user_id', $user->id)
            ->where('contact_id', $me->id)
            ->where('status', 'pending')
            ->exists();

        if (!$hasRequest) {
            return response()->json(['message' => 'Запрос не найден'], 404);
        }

        DB::table('contacts')
            ->where('user_id', $user->id)
            ->where('contact_id', $me->id)
            ->update(['status' => 'accepted']);

        broadcast(new ContactRequestAccepted($me, $user->id));

        return response()->json([
            'message' => 'Запрос принят',
            'contact_status' => ['type' => 'accepted', 'is_sender' => true],
            'contacts_count' => $this->getSymmetricCount($user, 'accepted'),
            'pending_contacts_count' => $this->getSymmetricCount($me, 'pending', 'receiver')
        ]);
    }

    private function getSymmetricCount(User $user, $status, $type = 'all')
    {
        $q = DB::table('contacts')->where('status', $status);
        if ($type === 'receiver') {
            return $q->where('contact_id', $user->id)->count();
        }
        return $q->where(fn($query) => $query
            ->where('user_id', $user->id)
            ->orWhere('contact_id', $user->id))
            ->count();
    }
}
