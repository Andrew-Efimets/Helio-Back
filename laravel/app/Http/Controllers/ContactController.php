<?php

namespace App\Http\Controllers;

use App\Events\ContactDeleted;
use App\Events\ContactRequestAccepted;
use App\Events\ContactRequestSent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function toggle(User $user)
    {
        /** @var \App\Models\User $me */
        $me = auth()->user();
        if ($me->id === $user->id) {
            return response()->json(['message' => 'Нельзя добавить самого себя'], 400);
        }

        $exists = DB::table('contacts')
            ->where(function($q) use ($me, $user) {
                $q->where('user_id', $me->id)->where('contact_id', $user->id);
            })
            ->orWhere(function($q) use ($me, $user) {
                $q->where('user_id', $user->id)->where('contact_id', $me->id);
            })
            ->exists();

        if ($exists) {

            $pivot = DB::table('contacts')
                ->where(function($q) use ($me, $user) {
                    $q->where('user_id', $me->id)->where('contact_id', $user->id);
                })
                ->orWhere(function($q) use ($me, $user) {
                    $q->where('user_id', $user->id)->where('contact_id', $me->id);
                })
                ->first();

            $status = $pivot ? $pivot->status : 'pending';

            DB::table('contacts')
                ->where(function($q) use ($me, $user) {
                    $q->where('user_id', $me->id)->where('contact_id', $user->id);
                })
                ->orWhere(function($q) use ($me, $user) {
                    $q->where('user_id', $user->id)->where('contact_id', $me->id);
                })
                ->delete();

            broadcast(new ContactDeleted($me, $user->id, $status));

            $message = $status === 'accepted' ? 'Контакт удалён' : 'Запрос отменён';

            return response()->json([
                'message' => $message,
                'contact_status' => null,
                'contacts_count' => $user->contacts()->count(),
                'pending_contacts_count' => auth()->user()->pending_contacts()->count()
            ]);
        }

        $me->contacts()->attach($user->id, ['status' => 'pending']);

        broadcast(new ContactRequestSent($me, $user->id))->toOthers();

        return response()->json([
            'message' => 'Запрос отправлен',
            'contact_status' => [
                'type' => 'pending',
                'is_sender' => false
            ],
            'contacts_count' => $user->contacts()->count(),
            'pending_contacts_count' => auth()->user()->pending_contacts()->count()
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

        DB::transaction(function () use ($me, $user) {
            DB::table('contacts')
                ->where(function($q) use ($me, $user) {
                    $q->where('user_id', $me->id)->where('contact_id', $user->id);
                })
                ->orWhere(function($q) use ($me, $user) {
                    $q->where('user_id', $user->id)->where('contact_id', $me->id);
                })
                ->update(['status' => 'accepted']);
            $me->contacts()->syncWithoutDetaching([
                $user->id => ['status' => 'accepted']
            ]);
        });

        broadcast(new ContactRequestAccepted($me, $user->id))->toOthers();

        return response()->json([
            'message' => 'Запрос принят',
            'contact_status' => [
                'type' => 'accepted',
                'is_sender' => true
            ],
            'contacts_count' => $user->contacts()->count(),
            'pending_contacts_count' => auth()->user()->pending_contacts()->count()
        ]);
    }
}
