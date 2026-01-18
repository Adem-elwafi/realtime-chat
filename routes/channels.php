<?php

use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    Log::info('🔐 Attempting to authorize user for chat channel', [
        'user_id' => $user->id,
        'user_id_type' => gettype($user->id),
        'conversation_id' => $conversationId,
        'conversation_id_type' => gettype($conversationId),
    ]);

    $conversation = \App\Models\Conversation::find($conversationId);

    if (! $conversation) {
        Log::warning('❌ Conversation not found', ['id' => $conversationId]);
        return false;
    }

    Log::info('✅ Conversation found', [
        'id' => $conversation->id,
        'user_one_id' => $conversation->user_one_id,
        'user_two_id' => $conversation->user_two_id,
    ]);

    $isParticipant = (
        $conversation->user_one_id == $user->id ||
        $conversation->user_two_id == $user->id
    );

    Log::info('✅ User access result', [
        'user_id' => $user->id,
        'allowed' => $isParticipant,
        'user_one' => $conversation->user_one_id,
        'user_two' => $conversation->user_two_id,
    ]);

    return $isParticipant;
});
// Global presence channel for tracking online users
Broadcast::channel('presence-online-users', function ($user) {
    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});