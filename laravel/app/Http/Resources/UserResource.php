<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

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
        $pivot = $this->getContactStatus();

        $permissions = [
            'phone'    =>
                $isMe || Gate::allows('viewParam', [$this->resource, 'show_phone']),
            'photo'    =>
                $isMe || Gate::allows('viewParam', [$this->resource, 'show_photo']),
            'video'    =>
                $isMe || Gate::allows('viewParam', [$this->resource, 'show_video']),
            'contacts' =>
                $isMe || Gate::allows('viewParam', [$this->resource, 'show_contacts']),
            'account'  =>
                $isMe || Gate::allows('viewParam', [$this->resource, 'show_account']),
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $permissions['phone'] ? $this->phone : null,

            'contact_status' => $pivot ? [
                'type' => $pivot->status,
                'is_sender' => (int)$pivot->user_id === (int)$this->id,
            ] : null,

            'avatar' => $this->activeAvatar?->avatar_url,

            'photos_count' => $permissions['photo']
                ? ($this->photos_count ?? 0)
                : 0,
            'videos_count' => $permissions['video']
                ? ($this->videos_count ?? 0)
                : 0,
            'contacts_count' => $permissions['contacts']
                ? ($this->contacts_count ?? 0)
                : 0,
            'pending_contacts_count' => $this->when($isMe, $this->pending_contacts_count ?? 0),
//            'unread_messages_count' => $this->when($isMe, $this->unread_messages_count ?? 0),

            'profile' => [
                'country' => $permissions['account']
                    ? $this->profile->country
                    : null,
                'city' => $permissions['account']
                    ? $this->profile->city
                    : null,
                'birthday' => $permissions['account']
                    ? $this->profile->birthday?->format('Y-m-d')
                    : null,
                'privacy' => $this->profile->privacy ?? [],
            ],
        ];
    }
}
