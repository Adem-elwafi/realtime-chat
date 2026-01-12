// resources/js/components/ChatMessages.jsx
import { useEffect, useState, useRef } from 'react';

export default function ChatMessages({
    conversationId,
    initialMessages,
    currentUserId
}) {
    const [messages, setMessages] = useState(initialMessages);

    // typing indicator state
    const [isTyping, setIsTyping] = useState(false);
    const [typingUser, setTypingUser] = useState(null);
    const typingHideTimeoutRef = useRef(null);

    const messagesEndRef = useRef(null);

    const addMessage = (incoming) => {
        setMessages((prev) => {
            if (prev.some((m) => m.id === incoming.id)) return prev;
            return [...prev, incoming];
        });
    };

    // auto scroll
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isTyping]);

    useEffect(() => {
        if (!window.Echo) {
            console.error('❌ Echo not available');
            return;
        }

        const channelName = `chat.${conversationId}`;
        const channel = window.Echo.private(channelName);

        /* -------------------------------
         | Message received
         -------------------------------- */
        channel.listen('MessageSent', (eventData) => {
            addMessage(eventData);
        });

        /* -------------------------------
         | Typing indicator
         -------------------------------- */
        channel.listen('.UserTyping', (e) => {
            // Ignore self
            if (e.user_id === currentUserId) return;

            // Show typing
            setTypingUser(e.user_name);
            setIsTyping(true);

            // 🔥 IMPORTANT: clear old timeout
            if (typingHideTimeoutRef.current) {
                clearTimeout(typingHideTimeoutRef.current);
            }

            // 🔥 Set a NEW timeout every time an event arrives
            typingHideTimeoutRef.current = setTimeout(() => {
                setIsTyping(false);
                setTypingUser(null);
                typingHideTimeoutRef.current = null;
            }, 3000);
        });
        /* -------------------------------
         | Local optimistic messages
         -------------------------------- */
        const localHandler = (evt) => {
            if (!evt.detail) return;
            addMessage(evt.detail);
        };

        window.addEventListener('message:sent', localHandler);

        return () => {
            window.Echo.leave(channelName);
            window.removeEventListener('message:sent', localHandler);

            if (typingHideTimeoutRef.current) {
                clearTimeout(typingHideTimeoutRef.current);
            }
        };
    }, [conversationId, currentUserId]);

    return (
        <div className="flex flex-col h-full">
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
                            {new Date(msg.created_at).toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit',
                            })}
                        </small>
                    </div>
                ))}

                {isTyping && (
                    <div className="text-sm text-gray-500 italic">
                        {typingUser || 'Someone'} is typing…
                    </div>
                )}

                <div ref={messagesEndRef} />
            </div>
        </div>
    );
}
