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

class MemberAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $chatId,
        public $chatTitle,
        public $initiatorId,
        public $initiatorName,
        public User $newMember
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
        return [
            new PrivateChannel('chats.' . $this->chatId),
            new PrivateChannel('user.' . $this->newMember->id),
        ];
    }

    public function broadcastAs()
    {
        return 'member.added';
    }

    public function broadcastWith(): array
    {
        return [
            'chatId' => $this->chatId,
            'chatTitle' => $this->chatTitle,
            'initiatorId' => $this->initiatorId,
            'initiatorName' => $this->initiatorName,
            'newMember' => [
                'id' => $this->newMember->id,
                'name' => $this->newMember->name,
                'avatar' => $this->newMember->activeAvatar?->avatar_url,
            ],
        ];
    }
}
