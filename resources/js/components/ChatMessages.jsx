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

    const addMessage = (incoming) => {
        setMessages((prev) => {
            if (prev.some((m) => m.id === incoming.id)) return prev; // avoid duplicates from Echo + local
            return [...prev, incoming];
        });
    };

    // Auto-scroll to bottom when messages change
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Listen for new messages via WebSocket
    useEffect(() => {
        console.log('🔌 Listening on private channel:', `chat.${conversationId}`);
        const channel = echo.private(`chat.${conversationId}`);

        // Log connection state
        if (window.Echo) {
            console.log('✅ Echo instance available');
            console.log('🌐 Broadcaster:', window.Echo.options.broadcaster);
        }

        channel.listen('MessageSent', (eventData) => {
            console.log('📩 New message received:', eventData);
            console.log('📊 Event data keys:', Object.keys(eventData));
            addMessage(eventData);
        });

        const localHandler = (evt) => {
            if (!evt.detail) return;
            console.log('🧭 Local message event received:', evt.detail);
            addMessage(evt.detail);
        };
        window.addEventListener('message:sent', localHandler);

        channel.subscribed(() => {
            console.log('✔️ Successfully subscribed to channel');
        });

        channel.error((error) => {
            console.error('❌ Channel subscription error:', error);
        });

        // Clean up on unmount
        return () => {
            console.log('🔌 Leaving channel:', `chat.${conversationId}`);
            echo.leave(`chat.${conversationId}`);
            window.removeEventListener('message:sent', localHandler);
        };
    }, [conversationId]);

    return (
        <div className="flex-1 overflow-y-auto p-4 space-y-3 max-h-[500px]">
            {messages.map((msg) => (
                <div
                    key={msg.id}
                    className={`max-w-xs p-3 rounded-lg ${
                        msg.sender_id === currentUserId
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