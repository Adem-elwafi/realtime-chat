<?php

// app/Events/UserTyping.php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $user_id;
    public string $user_name;
    public int $conversation_id;

    public function __construct(int $conversationId, User $user)
    {
        $this->conversation_id = $conversationId;
        $this->user_id = $user->id;
        $this->user_name = $user->name;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->conversation_id);
    }

    public function broadcastAs()
    {
        return 'UserTyping';
    }
}

