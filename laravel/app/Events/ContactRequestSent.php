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

class ContactRequestSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public int $senderId;
    public int $receiverId;

    public function __construct(User $sender, $receiverId)
    {
        $this->senderId = (int)$sender->id;
        $this->receiverId = (int)$receiverId;
        $this->message = "{$sender->name} хочет добавить вас в контакты";
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->receiverId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'contact.request';
    }
}
