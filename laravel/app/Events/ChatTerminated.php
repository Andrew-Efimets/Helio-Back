<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatTerminated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $chatId,
        public $type,
        public $userId = null,
        public $userName = null,
        public $participant = null,
    )
    {

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('chats.' . $this->chatId)];
        if ($this->participant) {
            $channels[] = new PrivateChannel('user.' . $this->participant->id);
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'chat.terminated';
    }

    public function broadcastWith(): array
    {
        return [
            'chatId'   => $this->chatId,
            'type'     => $this->type,
            'userId'   => $this->userId,
            'userName' => $this->userName,
        ];
    }
}
