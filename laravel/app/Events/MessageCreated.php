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

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load('user.activeAvatar');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chats.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
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
