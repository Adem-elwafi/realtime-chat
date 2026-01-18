// resources/js/components/ChatMessages.jsx
import React from 'react';
import { useEffect, useState, useRef } from 'react';
import axios from 'axios'; // ✅ Added

export default function ChatMessages({
        conversationId,
        initialMessages,
        currentUserId,
        otherUserId
    }) {
    const [messages, setMessages] = useState(initialMessages);
    const [isTyping, setIsTyping] = useState(false);
    const [typingUser, setTypingUser] = useState(null);
    const [deletingIds, setDeletingIds] = useState(new Set()); // Track deletions in progress
    const typingHideTimeoutRef = useRef(null);
    const messagesEndRef = useRef(null);
    // NEW: Online presence states
    const [otherUserOnline, setOtherUserOnline] = useState(false);
    const [otherUserLastSeen, setOtherUserLastSeen] = useState(null);


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

    // Handle message deletion
    const deleteMessage = async (messageId) => {
        // Confirm deletion
        if (!window.confirm('Delete this message?')) {
            return;
        }

        setDeletingIds(prev => new Set(prev).add(messageId));

        try {
            const response = await axios.delete(`/chat/message/${messageId}`);
            
            if (response.data.success) {
                console.log('🗑️ Message deleted successfully:', messageId);
                
                // Optimistic UI removal
                setMessages(prev => prev.filter(m => m.id !== messageId));
            }
        } catch (error) {
            console.error('❌ Failed to delete message:', error);
            alert('Failed to delete message. Please try again.');
        } finally {
            setDeletingIds(prev => {
                const next = new Set(prev);
                next.delete(messageId);
                return next;
            });
        }
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
        // NEW: Global presence channel for online status
        const presenceChannel = window.Echo.join('presence-online-users');

        // Get all currently online users (called once on join)
        presenceChannel.here((users) => {
            console.log('Currently online users:', users);
            // Check if the other user is in the list
            const isOtherOnline = users.some(u => u.id !== currentUserId);
            setOtherUserOnline(isOtherOnline);
        });

        // When someone joins (including the other user)
        presenceChannel.joining((user) => {
            console.log('User joined:', user);
            if (user.id !== currentUserId) {
                setOtherUserOnline(true);
                setOtherUserLastSeen(null); // Reset last seen when they come online
            }
        });

        // When someone leaves
        presenceChannel.leaving((user) => {
            console.log('User left:', user);
            if (user.id !== currentUserId) {
                setOtherUserOnline(false);
                setOtherUserLastSeen(new Date()); // Mark approximate disconnect time
            }
        });
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
            console.log('📝 UserTyping event received:', e);
            
            if (!e || e.user_id === currentUserId) return;

            setTypingUser(e.user_name);
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

        // Message deletion
        const handleDeleteReceipt = (e) => {
            console.log('🗑️ MessageDeleted event received:', {
                messageId: e.messageId,
                conversationId: e.conversationId,
                deletedBy: e.deletedBy
            });
            
            setMessages(prevMessages => {
                const updated = prevMessages.filter(msg => msg.id !== e.messageId);
                console.log('✅ Message removed from state after deletion');
                return updated;
            });
        };

        console.log('🎧 Listening for MessageDeleted on channel:', channelName);
        channel.listen('.MessageDeleted', handleDeleteReceipt);

        // Local optimistic messages
        const localHandler = (evt) => {
            if (evt.detail) addMessage(evt.detail);
        };
        window.addEventListener('message:sent', localHandler);

        // Cleanup
        return () => {
            window.Echo.leave(channelName);
            window.Echo.leave('presence-online-users'); // NEW: leave presence channel
            window.removeEventListener('message:sent', localHandler);
            channel.stopListening('.MessageRead', handleReadReceipt);
            channel.stopListening('.MessageDeleted', handleDeleteReceipt);
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
        {/* NEW: Online status header */}
        <div className="p-4 border-b bg-gray-50 dark:bg-gray-800">
            <div className="flex items-center gap-3">
                <div className="relative">
                    <div className={`w-3 h-3 rounded-full ${
                        otherUserOnline ? 'bg-green-900' : 'bg-gray-400'
                    }`}></div>
                    {otherUserOnline && (
                        <div className="absolute inset-0 rounded-full animate-ping bg-green-400 opacity-75"></div>
                    )}
                </div>
                <div>
                    <p className="font-medium">Chat with [Other User]</p> {/* You can fetch name later */}
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        {otherUserOnline 
                            ? 'Active now' 
                            : otherUserLastSeen 
                                ? `Last seen ${formatMessageTime(otherUserLastSeen)}` 
                                : 'Offline'}
                    </p>
                </div>
            </div>
        </div>
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
                            className={`p-3 rounded-lg relative group ${
                                isOwn
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-800'
                            }`}
                        >
                            <p>{msg.body}</p>
                            <small className="opacity-75 text-xs block mt-1">
                                {formatMessageTime(msg.created_at)}
                            </small>

                            {/* Delete button - only for own messages */}
                            {isOwn && (
                                <button
                                    onClick={() => deleteMessage(msg.id)}
                                    disabled={deletingIds.has(msg.id)}
                                    className="absolute top-0 right-0 opacity-0 group-hover:opacity-100 transition-opacity bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white px-1.5 py-0.5 text-xs"
                                    title="Delete message"
                                >
                                    {deletingIds.has(msg.id) ? '⏳' : '✕'}
                                </button>
                            )}
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