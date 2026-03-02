<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $chatId;

    public function __construct($chatId, $messageId)
    {
        $this->chatId = $chatId;
        $this->messageId = $messageId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chats.' . $this->chatId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}
