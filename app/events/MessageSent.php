<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new message is sent in a conversation.
 * This event is broadcast to all participants of the conversation
 * via a private WebSocket channel for real-time updates.
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance that was just created.
     *
     * @var \App\Models\Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Message  $message
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        Log::info('✉️ MessageSent event created', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
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
        $channel = 'chat.' . $this->message->conversation_id;
        Log::info('📡 Broadcasting on channel', ['channel' => $channel]);
        return new PrivateChannel($channel);
    }

    /**
     * Get the data to broadcast to clients.
     *
     * Only send necessary, non-sensitive data.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $data = [
            'id' => $this->message->id,
            'body' => $this->message->message, // ← was 'body', now 'message'
            'sender_id' => $this->message->sender_id,
            'sender_name' => optional($this->message->sender)->name ?? 'Unknown',
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
        Log::info('📤 Broadcasting data', $data);
        return $data;
    }
}