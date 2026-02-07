<?php

namespace App\Events;

use App\Models\Photo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhotoProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Photo $photo) {}


    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->photo->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PhotoProcessed';
    }

    public function broadcastWith(): array
    {
        return [
            'photo' => $this->photo,
        ];
    }
}
