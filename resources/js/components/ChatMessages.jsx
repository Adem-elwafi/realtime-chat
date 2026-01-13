// resources/js/components/ChatMessages.jsx
import { useEffect, useState, useRef } from 'react';
import axios from 'axios'; // ✅ Added

export default function ChatMessages({
    conversationId,
    initialMessages,
    currentUserId
}) {
    const [messages, setMessages] = useState(initialMessages);
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

    // Auto-scroll to bottom
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isTyping]);

    // Mark messages as read when conversation opens
    const markMessagesAsRead = async () => {
        console.log('📖 Marking messages as read for conversation:', conversationId);
        try {
            const response = await axios.post(`/chat/${conversationId}/read`);
            const { message_ids } = response.data;

            console.log('✅ Messages marked as read:', message_ids);

            setMessages(prevMessages =>
                prevMessages.map(msg =>
                    message_ids.includes(msg.id) ? { ...msg, is_read: true } : msg
                )
            );
        } catch (error) {
            console.error('❌ Failed to mark messages as read:', error);
        }
    }; // ✅ Closed properly

    // Echo listeners
    useEffect(() => {
        if (!window.Echo) {
            console.error('❌ Echo not available');
            return;
        }

        const channelName = `chat.${conversationId}`;
        const channel = window.Echo.private(channelName);

        // New message
        channel.listen('MessageSent', (eventData) => {
            addMessage(eventData);
        });

        // Typing indicator
        channel.listen('.UserTyping', (e) => {
            if (!e || !e.user || e.user.id === currentUserId) return;

            setTypingUser(e.user.name);
            setIsTyping(true);

            if (typingHideTimeoutRef.current) {
                clearTimeout(typingHideTimeoutRef.current);
            }

            typingHideTimeoutRef.current = setTimeout(() => {
                setIsTyping(false);
                setTypingUser(null);
                typingHideTimeoutRef.current = null;
            }, 3000);
        });

        // Read receipts
        const handleReadReceipt = (e) => {
            setMessages(prevMessages =>
                prevMessages.map(msg =>
                    e.messageIds.includes(msg.id) ? { ...msg, is_read: true } : msg
                )
            );
        };

        channel.listen('MessageRead', handleReadReceipt);

        // Local optimistic messages
        const localHandler = (evt) => {
            if (evt.detail) addMessage(evt.detail);
        };
        window.addEventListener('message:sent', localHandler);

        // Cleanup
        return () => {
            window.Echo.leave(channelName);
            window.removeEventListener('message:sent', localHandler);
            channel.stopListening('MessageRead', handleReadReceipt);
            if (typingHideTimeoutRef.current) {
                clearTimeout(typingHideTimeoutRef.current);
            }
        };
    }, [conversationId, currentUserId]);

    // Trigger read on mount
    useEffect(() => {
        markMessagesAsRead();
    }, [conversationId]);

    return (
        <div className="flex flex-col h-full">
            <div className="flex-1 overflow-y-auto p-4 space-y-3 max-h-[500px]">
                {messages.map((msg) => {
                    const isOwn = msg.sender_id === currentUserId;
                    return (
                        <div
                            key={msg.id}
                            className={`max-w-xs p-3 rounded-lg relative ${
                                isOwn
                                    ? 'bg-blue-500 text-white ml-auto'
                                    : 'bg-gray-200 text-gray-800 mr-auto'
                            }`}
                        >
                            <p>{msg.body}</p>
                            <small className="opacity-75 text-xs block mt-1">
                                {new Date(msg.created_at).toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })}
                            </small>

                            {/* ✅ Read receipt checkmarks */}
                            {isOwn && (
                                <span className="absolute -bottom-5 right-0 text-xs">
                                    {msg.is_read ? (
                                        <span title="Read" className="text-blue-300">✓✓</span>
                                    ) : (
                                        <span title="Sent" className="text-gray-300">✓</span>
                                    )}
                                </span>
                            )}
                        </div>
                    );
                })}

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