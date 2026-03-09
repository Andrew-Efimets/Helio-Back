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

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->load('user.activeAvatar');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chats.' . $this->message->chat_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'chat_id' => $this->message->chat_id,
                'content' => $this->message->content,
                'updated_at' => $this->message->updated_at,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                    'active_avatar' => [
                        'avatar_url' => $this->message->user->activeAvatar?->avatar_url
                    ]
                ]
            ]
        ];
    }
}
