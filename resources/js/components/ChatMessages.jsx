// resources/js/components/ChatMessages.jsx
import { useEffect, useState, useRef } from 'react';

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
        console.log('🔌 ChatMessages component mounted');
        console.log('📋 Conversation ID:', conversationId);
        console.log('👤 Current User ID:', currentUserId);
        console.log('📨 Initial Messages Count:', initialMessages.length);

        if (!window.Echo) {
            console.error('❌ Echo instance not available!');
            return;
        }

        console.log('✅ Echo instance available');
        
        if (window.Echo) {
            console.log('🌐 Echo Configuration:', {
                broadcaster: window.Echo.options.broadcaster,
                wsHost: window.Echo.options.wsHost,
                wsPort: window.Echo.options.wsPort,
                key: window.Echo.options.key,
                forceTLS: window.Echo.options.forceTLS,
            });
            console.log('🔗 WebSocket URL:', `${window.Echo.options.forceTLS ? 'wss' : 'ws'}://${window.Echo.options.wsHost}:${window.Echo.options.wsPort}`);
        }

        const channelName = `chat.${conversationId}`;
        console.log('🔌 Attempting to subscribe to private channel:', channelName);
        
        const channel = window.Echo.private(channelName);
        
        console.log('📞 Channel object created:', channel);
        console.log('📞 Channel name:', channel.name);

        channel.subscribed(() => {
            console.log('✔️ ✔️ ✔️ SUCCESSFULLY SUBSCRIBED TO CHANNEL:', channelName);
            console.log('⏰ Subscription timestamp:', new Date().toISOString());
        });

        channel.error((error) => {
            console.error('❌ ❌ ❌ CHANNEL SUBSCRIPTION ERROR:', error);
            console.error('❌ Error type:', typeof error);
            console.error('❌ Error details:', JSON.stringify(error, null, 2));
        });
        
        // Add timeout to detect if subscription never completes
        setTimeout(() => {
            console.warn('⚠️ ⚠️ ⚠️ SUBSCRIPTION TIMEOUT - Still waiting after 5 seconds!');
            console.warn('⚠️ This means the WebSocket connection is not being established');
            console.warn('⚠️ Check: 1) Reverb is running, 2) Port 8081 is open, 3) Auth endpoint works');
        }, 5000);

        channel.listen('MessageSent', (eventData) => {
            console.log('📩 NEW MESSAGE RECEIVED via WebSocket');
            console.log('📊 Event data:', eventData);
            console.log('📊 Event data keys:', Object.keys(eventData));
            console.log('💬 Message ID:', eventData.id);
            console.log('💬 Message body:', eventData.body);
            console.log('💬 Sender ID:', eventData.sender_id);
            console.log('💬 Sender name:', eventData.sender_name);
            console.log('⏰ Received at:', new Date().toISOString());
            
            if (!eventData.id) {
                console.error('⚠️ WARNING: Received message without ID');
            }
            if (!eventData.body) {
                console.error('⚠️ WARNING: Received message without body');
            }
            
            addMessage(eventData);
        });

        const localHandler = (evt) => {
            if (!evt.detail) return;
            console.log('🧭 Local message event received:', evt.detail);
            addMessage(evt.detail);
        };
        window.addEventListener('message:sent', localHandler);

        // Clean up on unmount
        return () => {
            console.log('🔌 Component unmounting - leaving channel:', channelName);
            window.Echo.leave(channelName);
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