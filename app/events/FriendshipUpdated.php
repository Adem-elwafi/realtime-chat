<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendshipUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Friendship $friendship;
    public int $userId;

    public function __construct(Friendship $friendship, int $userId)
    {
        $this->friendship = $friendship;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('friends.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'FriendshipUpdated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->friendship->id,
            'requester_id' => $this->friendship->requester_id,
            'addressee_id' => $this->friendship->addressee_id,
            'status' => $this->friendship->status,
            'created_at' => $this->friendship->created_at?->toIso8601String(),
        ];
    }
}
