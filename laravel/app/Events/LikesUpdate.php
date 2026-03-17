<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikesUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $likes;
    public $type;
    public $id;

    public function __construct($likes, $type, $id)
    {
        $this->likes = $likes;
        $this->type = $type;
        $this->id = $id;
    }

    public function broadcastOn()
    {
        return new Channel("likes.{$this->type}.{$this->id}");
    }
}
