<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $message,
        public $receiverId
    ) {
        $this->message = $message->load('user.activeAvatar');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->receiverId)];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'chat_id' => $this->message->chat_id,
                'user_id' => $this->message->user_id,
                'content' => $this->message->content,
                'created_at' => $this->message->created_at,
                'parent_id' => $this->message->parent_id,
                'parent_content' => $this->message->parent_content,
                'parent_user_name' => $this->message->parent_user_name,
                'parent_user_avatar' => $this->message->parent_user_avatar,
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
