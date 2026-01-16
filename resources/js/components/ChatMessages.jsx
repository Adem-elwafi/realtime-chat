// resources/js/components/ChatMessages.jsx
import React from 'react';
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

    // NEW: Smart timestamp formatter
const formatMessageTime = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();

    // Safety check for invalid or future dates
    if (isNaN(date.getTime()) || date > now) {
        return 'Invalid date';
    }

    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    // Same day → relative time
    if (diffDays === 0) {
        if (diffMins < 1) return 'Just now';           // up to ~59 seconds
        if (diffMins < 60) return `${diffMins}m ago`;
        return `${diffHours}h ago`;
    }

    // Yesterday
    if (diffDays === 1) {
        return `Yesterday ${date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true  // ← makes it 3:45 PM instead of 15:45 (change to false for 24h)
        })}`;
    }

    // Older messages
    return date.toLocaleDateString([], {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    }) + ' ' + date.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

    const formatDateHeader = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();

    // Reset times to compare only dates
    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (dateOnly.getTime() === today.getTime()) {
        return 'Today';
    }

    if (dateOnly.getTime() === yesterday.getTime()) {
        return 'Yesterday';
    }

    // Older dates - full format (you can customize this)
    return date.toLocaleDateString([], {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

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
        console.log('👤 Current user ID:', currentUserId);
        try {
            const response = await axios.post(`/chat/${conversationId}/read`);
            const { message_ids } = response.data;

            console.log('✅ Messages marked as read on backend:', message_ids);
            console.log('📡 Backend should now broadcast MessageRead event to other users');

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

        console.log('🔌 Subscribing to channel:', channelName);
        console.log('🎯 Current user ID:', currentUserId);

        // Debug: Log ALL events on this channel
        channel.listenToAll((eventName, data) => {
            console.log('🌟 RAW EVENT RECEIVED:', eventName, data);
        });

        // New message
        channel.listen('MessageSent', (eventData) => {
            console.log('📨 MessageSent received:', eventData);
            addMessage(eventData);
            
            // Auto-mark as read if this message is from the other person
            if (eventData.sender_id !== currentUserId) {
                console.log('📖 Auto-marking new message as read (sender is other user)');
                setTimeout(() => markMessagesAsRead(), 100);
            }
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

        // Read receipts - Note: Laravel adds a dot prefix for custom events
        const handleReadReceipt = (e) => {
            console.log('📩 MessageRead event received:', {
                messageIds: e.messageIds,
                conversationId: e.conversationId,
                currentUserId: currentUserId
            });
            
            setMessages(prevMessages => {
                const updated = prevMessages.map(msg =>
                    e.messageIds.includes(msg.id) ? { ...msg, is_read: true } : msg
                );
                console.log('✅ Messages state updated after MessageRead');
                return updated;
            });
        };

        console.log('🎧 Listening for MessageRead on channel:', channelName);
        channel.listen('.MessageRead', handleReadReceipt);

        // Local optimistic messages
        const localHandler = (evt) => {
            if (evt.detail) addMessage(evt.detail);
        };
        window.addEventListener('message:sent', localHandler);

        // Cleanup
        return () => {
            window.Echo.leave(channelName);
            window.removeEventListener('message:sent', localHandler);
            channel.stopListening('.MessageRead', handleReadReceipt);
            if (typingHideTimeoutRef.current) {
                clearTimeout(typingHideTimeoutRef.current);
            }
        };
    }, [conversationId, currentUserId]);

    // Trigger read on mount
    useEffect(() => {
        markMessagesAsRead();
    }, [conversationId]);

    // Compute the last message sent by the current user
    const lastOwnMessageId = [...messages]
        .reverse()
        .find(m => m.sender_id === currentUserId)?.id;

    return (
        <div className="flex flex-col h-full">
            <div className="flex-1 overflow-y-auto p-4 space-y-3 max-h-[500px]">
                {messages.length > 0 && (
            <>
        {/* First message always gets a date header */}
        <div className="flex items-center my-6">
            <div className="flex-1 h-px bg-gray-300 dark:bg-gray-600"></div>
            <span className="mx-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                {formatDateHeader(messages[0].created_at)}
            </span>
            <div className="flex-1 h-px bg-gray-300 dark:bg-gray-600"></div>
        </div>

        {messages.map((msg, index) => {
            const isOwn = msg.sender_id === currentUserId;
            const showDivider = index > 0 && 
                formatDateHeader(msg.created_at) !== formatDateHeader(messages[index - 1].created_at);

            return (
                <React.Fragment key={msg.id}>
                    {showDivider && (
                        <div className="flex items-center my-6">
                            <div className="flex-1 h-px bg-gray-300 dark:bg-gray-600"></div>
                            <span className="mx-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                                {formatDateHeader(msg.created_at)}
                            </span>
                            <div className="flex-1 h-px bg-gray-300 dark:bg-gray-600"></div>
                        </div>
                    )}

                    <div
                        className={`max-w-xs ${isOwn ? 'ml-auto' : 'mr-auto'} flex flex-col`}
                    >
                        <div
                            className={`p-3 rounded-lg ${
                                isOwn
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-800'
                            }`}
                        >
                            <p>{msg.body}</p>
                            <small className="opacity-75 text-xs block mt-1">
                                {formatMessageTime(msg.created_at)}
                            </small>
                        </div>

                        {/* Read receipt checkmarks */}
                        {isOwn && msg.id === lastOwnMessageId && (
                            <div className="mt-1 flex items-center justify-end gap-1 text-[11px]">
                                <span
                                    title={msg.is_read ? 'Read' : 'Sent'}
                                    className={msg.is_read ? 'text-blue-400' : 'text-gray-400'}
                                >
                                    {msg.is_read ? '✓✓' : '✓'}
                                </span>
                            </div>
                        )}
                    </div>
                </React.Fragment>
            );
        })}
    </>
)}
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