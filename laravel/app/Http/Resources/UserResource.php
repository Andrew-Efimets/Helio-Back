<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_contact' => auth()->user() ? auth()->user()
                ->isContactWith($this->id) : false,
            'avatar' => $this->avatars
                ->where('is_active', true)
                ->first()?->avatar_url,

            'profile' => [
                'country' => $this->profile->country,
                'city' => $this->profile->city,
                'birthday' => $this->profile->birthday?->format('Y-m-d'),
                'privacy' => $this->profile->privacy,
            ]
        ];
    }
}
