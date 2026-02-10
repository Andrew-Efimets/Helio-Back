<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $senderId;
    public string $senderName;
    public int $receiverId;
    public string $status;

    public function __construct(User $sender, int $receiverId, string $status)
    {
        $this->senderId = $sender->id;
        $this->senderName = $sender->name;
        $this->receiverId = $receiverId;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->receiverId)];
    }

    public function broadcastAs(): string
    {
        return 'contact.deleted';
    }
}
