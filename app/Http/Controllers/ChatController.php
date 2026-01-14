<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Events\messageRead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreMessageRequest;
use App\Events\MessageSent;
use Illuminate\Http\JsonResponse;
use App\Events\UserTyping;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's conversations.
     *
     * This method fetches all conversations for the authenticated user,
     * ordered by the last message timestamp (most recent first).
     * It also calculates unread message counts for each conversation.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {   
        // Get the authenticated user
        $user = Auth::user();
        
        // Get all conversations for this user with the latest message preview
        $conversations = $user->conversations->map(function ($conversation) use ($user) {
            // Get the other user in the conversation
            $otherUser = $conversation->getOtherUser($user->id);
            
            // Get the latest message for preview (if any)
            $latestMessage = $conversation->messages->last();
            $messagePreview = $latestMessage ? substr($latestMessage->message, 0, 50) : 'No messages yet';
            
            // Count unread messages from the other user
            $unreadCount = $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
            
            return [
                'conversation' => $conversation,
                'other_user' => $otherUser,
                'latest_message' => $latestMessage,
                'message_preview' => $messagePreview,
                'unread_count' => $unreadCount,
            ];
        });
        
        return view('chat.index', compact('conversations'));
    }

    /**
     * Display the chat interface with a specific user.
     *
     * This method handles showing the chat window with another user.
     * If a conversation doesn't exist between the users, it creates one automatically.
     * It loads all messages in the conversation and marks them as read for the current user.
     *
     * @param int $userId The ID of the user to chat with
     * @return \Illuminate\View\View
     */
public function show($userId)
{
    $user = Auth::user();
    $otherUser = User::findOrFail($userId);
    $conversation = $user->getConversationWith($otherUser->id);

    // Prepare messages in format expected by React
    $messagesForReact = $conversation->messages->map(function ($msg) {
        return [
            'id' => $msg->id,
            'body' => $msg->message, // ← your DB column is 'message'
            'sender_id' => $msg->sender_id,
            'sender_name' => optional($msg->sender)->name ?? 'Unknown',
            'is_read' => (bool) $msg->is_read,
            'created_at' => $msg->created_at->toIso8601String(),
        ];
    })->values();

    return view('chat.show', compact('conversation', 'otherUser', 'messagesForReact'));
}

    /**
     * Store a new message in a conversation.
     *
     * This method handles sending a new message. It validates the input,
     * creates the message, updates the conversation's last_message_at timestamp,
     * and redirects back to the chat.
     *
     * @param StoreMessageRequest $request The validated request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreMessageRequest $request): JsonResponse
{    

    $user = Auth::user();

    // Determine the conversation
    if ($request->has('conversation_id')) {
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Verify the user is part of this conversation
        if ($conversation->user_one_id != $user->id && $conversation->user_two_id != $user->id) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
    } else {
        $otherUser = User::findOrFail($request->user_id);
        $conversation = $user->getConversationWith($otherUser->id);
    }

    // Create the new message
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
        'message' => $request->message, // 👈 Note: your field is 'message', not 'body'
        'is_read' => false,
    ]);

    Log::info('💾 Message saved to database', [
        'message_id' => $message->id,
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
        'message_preview' => substr($message->message, 0, 50),
    ]);

    // Update conversation timestamp
    $conversation->update(['last_message_at' => now()]);

    // 🔥 Broadcast the event
    Log::info('🚀 About to broadcast MessageSent event', [
        'message_id' => $message->id,
        'conversation_id' => $conversation->id,
        'channel_will_be' => 'chat.' . $conversation->id,
    ]);
    
    broadcast(new MessageSent($message));
    
    Log::info('✅ Broadcast completed', [
        'message_id' => $message->id,
        'timestamp' => now()->toIso8601String(),
    ]);

    // Return JSON response for React
    return response()->json([
        'message' => [
            'id' => $message->id,
            'body' => $message->message, // 👈 map 'message' → 'body' for frontend consistency
            'sender_id' => $message->sender_id,
            'sender_name' => $user->name,
            'is_read' => false, // New messages are unread
            'created_at' => $message->created_at->toIso8601String(),
        ],
    ], 201);
}

    /**
     * Mark all messages in a conversation as read.
     *
     * This method marks all unread messages from the other user as read.
     * This is useful when the user opens a conversation or manually marks as read.
     *
     * @param int $conversationId The ID of the conversation
     * @return \Illuminate\Http\RedirectResponse
     */
        public function markMessagesAsRead(\App\Models\Conversation $conversation)
        {
        $userId = auth()->id();
        
        \Log::info('📖 markMessagesAsRead called', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
        
        // Check if user is participant (user_one or user_two)
        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            \Log::warning('❌ Unauthorized access attempt', [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ]);
            abort(403, 'Unauthorized access to this conversation');
        }

        $unreadMessages = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->get();

        \Log::info('📊 Unread messages found', [
            'count' => $unreadMessages->count(),
            'message_ids' => $unreadMessages->pluck('id')->toArray(),
        ]);

        if ($unreadMessages->isEmpty()) {
            return response()->json(['message_ids' => []]);
        }

        $messageIds = $unreadMessages->pluck('id')->toArray();
        Message::whereIn('id', $messageIds)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        \Log::info('✅ Messages marked as read', ['message_ids' => $messageIds]);

        // Broadcast the read receipt event in real-time
        \Log::info('📡 Broadcasting MessageRead event', [
            'message_ids' => $messageIds,
            'conversation_id' => $conversation->id,
            'channel' => 'chat.' . $conversation->id
        ]);
        
        broadcast(new \App\Events\MessageRead($messageIds, $conversation->id));
        
        \Log::info('✅ Broadcast sent');

        return response()->json(['message_ids' => $messageIds]);
    }

    public function sendTypingIndicator(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        // Ensure user is part of this conversation (authorization)
        $conversation = \App\Models\Conversation::findOrFail($request->conversation_id);
        $userId = auth()->id();
        
        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            abort(403, 'Unauthorized');
        }

        // Broadcast typing event
        broadcast(new UserTyping($request->conversation_id));

        return response()->json(['success' => true]);
    }
}