<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShortResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $me = auth()->id();
        $pivot = $this->contactPivot;

        return [
            'id' => $this->id,
            'name' => $this->name,

            'contact_status' => $pivot ? [
                'type' => $pivot->status,
                'is_sender' => (int)$pivot->user_id === (int)$me,
            ] : null,

            'avatar' => $this->activeAvatar?->avatar_url,

            'profile' => [
                'country' => $this->profile?->country,
                'city' => $this->profile?->city,
                'birthday' => $this->profile?->birthday?->format('Y-m-d'),
            ],
        ];
    }
}
