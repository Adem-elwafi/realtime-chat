<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a message is deleted.
 * This event is broadcast to all participants of the conversation
 * via a private WebSocket channel for real-time updates.
 */
class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * The ID of the deleted message.
     *
     * @var int
     */
    public $messageId;

    /**
     * The ID of the conversation the message belonged to.
     *
     * @var int
     */
    public $conversationId;

    /**
     * The ID of the user who deleted the message.
     *
     * @var int
     */
    public $deletedBy;

    /**
     * Create a new event instance.
     *
     * @param  int  $messageId
     * @param  int  $conversationId
     * @param  int  $deletedBy
     * @return void
     */
    public function __construct(int $messageId, int $conversationId, int $deletedBy)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->deletedBy = $deletedBy;

        Log::info('🗑️ MessageDeleted event created', [
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'deleted_by' => $deletedBy,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * We use a private channel named 'chat.{conversation_id}'
     * so only authorized participants can listen.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->conversationId);
    }

    /**
     * Get the name of the event to broadcast as.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MessageDeleted';
    }
}
