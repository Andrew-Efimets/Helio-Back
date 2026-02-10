<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $me = auth()->id();
        $isMe = $me === $this->id;

        $pivot = null;
        if ($me && !$isMe) {
            $pivot = DB::table('contacts')
                ->where(function ($q) use ($me) {
                    $q->where('user_id', $me)->where('contact_id', $this->id);
                })
                ->orWhere(function ($q) use ($me) {
                    $q->where('user_id', $this->id)->where('contact_id', $me);
                })
                ->first();
        }

        $isContact = $pivot && $pivot->status === 'accepted';

        $privacy = (array)($this->profile->privacy ?? []);

        $canSee = function ($key, $default = 'private') use ($privacy, $isMe, $isContact) {
            if ($isMe) return true;
            $setting = $privacy[$key] ?? $default;
            if ($setting === 'public') return true;
            if ($setting === 'contacts_only' && $isContact) return true;
            return false;
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $canSee('show_phone') ? $this->phone : null,

            'contact_status' => $pivot ? [
                'type' => $pivot->status,
                'is_sender' => (int)$pivot->user_id === (int)$me,
            ] : null,

            'avatar' => $this->avatars->where('is_active', true)->first()?->avatar_url,

            'photos_count' => $canSee('show_photo', 'public') ? ($this->photos_count ?? 0) : 0,
            'videos_count' => $canSee('show_video', 'public') ? ($this->videos_count ?? 0) : 0,
            'contacts_count' => $canSee('show_contacts', 'public') ? ($this->contacts_count ?? 0) : 0,
            'pending_contacts_count' => $isMe ? ($this->pending_contacts_count ?? 0) : 0,
            'unread_messages_count' => $isMe ? ($this->unread_messages_count ?? 0) : 0,

            'profile' => [
                'country' => $canSee('show_account') ? $this->profile->country : null,
                'city' => $canSee('show_account') ? $this->profile->city : null,
                'birthday' => $canSee('show_account') ? $this->profile->birthday?->format('Y-m-d') : null,
                'privacy' => $privacy,
            ],
        ];
    }
}
