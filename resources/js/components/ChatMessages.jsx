// resources/js/components/ChatMessages.jsx
import { useEffect, useState, useRef } from 'react';
import echo from '../echo';

/**
 * Displays chat messages and listens for real-time updates.
 * 
 * Props:
 * - conversationId: ID of the current conversation
 * - initialMessages: Array of messages loaded from Blade
 * - currentUserId: ID of logged-in user
 */
export default function ChatMessages({ conversationId, initialMessages, currentUserId }) {
    const [messages, setMessages] = useState(initialMessages);
    const messagesEndRef = useRef(null);

    // Auto-scroll to bottom when messages change
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Listen for new messages via WebSocket
    useEffect(() => {
        const channel = echo.private(`chat.${conversationId}`);

        channel.listen('MessageSent', (eventData) => {
            setMessages(prev => [...prev, eventData]);
        });

        // Clean up on unmount
        return () => {
            echo.leave(`chat.${conversationId}`);
        };
    }, [conversationId]);

    return (
        <div className="flex-1 overflow-y-auto p-4 space-y-3 max-h-[500px]">
            {messages.map((msg) => (
                <div
                    key={msg.id}
                    className={`max-w-xs p-3 rounded-lg ${
                        msg.sender_id === currentUsageId
                            ? 'bg-blue-500 text-white ml-auto'
                            : 'bg-gray-200 text-gray-800 mr-auto'
                    }`}
                >
                    <p>{msg.body}</p>
                    <small className="opacity-75 text-xs">
                        {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </small>
                </div>
            ))}
            <div ref={messagesEndRef} />
        </div>
    );
}