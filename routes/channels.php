<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // Find the conversation by ID
    $conversation = Conversation::find($conversationId);

    // If conversation doesn't exist, deny access
    if (! $conversation) {
        return false;
    }

    // Check if the current user is one of the participants
    // We assume your Conversation model has a relationship like:
    //   public function participants() { return $this->belongsToMany(User::class); }
    return $conversation->participants->contains($user);
});