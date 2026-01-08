<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreMessageRequest;

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
        // Get the authenticated user
        $currentUser = Auth::user();
        
        // Validate that we're not trying to chat with ourselves
        if ($currentUser->id == $userId) {
            return redirect()->route('chat.index')->with('error', 'You cannot chat with yourself.');
        }
        
        // Find or create conversation with the specified user
        $conversation = $currentUser->getConversationWith($userId);
        
        // Get the other user
        $otherUser = $conversation->getOtherUser($currentUser->id);
        
        // Load all messages in this conversation
        $messages = $conversation->messages()->with('sender')->get();
        
        // Mark all messages from the other user as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $currentUser->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        return view('chat.show', compact('conversation', 'otherUser', 'messages'));
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
    public function store(StoreMessageRequest $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Determine the conversation
        if ($request->has('conversation_id')) {
            // Use existing conversation
            $conversation = Conversation::findOrFail($request->conversation_id);
            
            // Verify the user is part of this conversation
            if ($conversation->user_one_id != $user->id && $conversation->user_two_id != $user->id) {
                return redirect()->route('chat.index')->with('error', 'Unauthorized access.');
            }
        } else {
            // Create conversation with specified user
            $otherUser = User::findOrFail($request->user_id);
            $conversation = $user->getConversationWith($otherUser->id);
        }
        
        // Create the new message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);
        
        // Update the conversation's last_message_at timestamp
        $conversation->update(['last_message_at' => now()]);
        
        return redirect()->route('chat.show', $conversation->getOtherUser($user->id)->id)
            ->with('success', 'Message sent successfully!');
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
    public function markAsRead($conversationId)
    {
        $user = Auth::user();
        
        // Find the conversation and verify user access
        $conversation = Conversation::where('id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('user_one_id', $user->id)
                      ->orWhere('user_two_id', $user->id);
            })
            ->firstOrFail();
        
        // Mark all messages from the other user as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        return redirect()->back()->with('success', 'Messages marked as read.');
    }
}