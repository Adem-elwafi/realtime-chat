// resources/js/components/MessageForm.jsx

import { useState, useRef, useEffect } from 'react';
import EmojiPicker from 'emoji-picker-react';

export default function MessageForm({ conversationId }) {
    const [body, setBody] = useState('');
    const [loading, setLoading] = useState(false);
    const [showPicker, setShowPicker] = useState(false);

    const lastTypingSentRef = useRef(0);
    const typingTimeoutRef = useRef(null);
    const lastTypingRef = useRef(0);
    const stopTypingTimeout = useRef(null);
    const pickerRef = useRef(null);
    const textareaRef = useRef(null);

    // Close picker when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (pickerRef.current && !pickerRef.current.contains(event.target)) {
                setShowPicker(false);
            }
        };

        if (showPicker) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [showPicker]);

    const sendTypingIndicator = async () => {
        try {
            await fetch('/chat/typing', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                }),
            });
        } catch (e) {
            // silent fail — typing indicator is non-critical
        }
    };


const handleChange = (e) => {
    const value = e.target.value;
    setBody(value);
    autoGrowTextarea();

    if (!value.trim()) return;

    const now = Date.now();

    if (now - lastTypingRef.current > 2500) {
        fetch('/chat/typing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .content,
            },
            body: JSON.stringify({ conversation_id: conversationId }),
        });

        lastTypingRef.current = now;
    }

    clearTimeout(stopTypingTimeout.current);
    stopTypingTimeout.current = setTimeout(() => {
        lastTypingRef.current = 0;
    }, 1000);
};

    // Auto-grow textarea to fit content
    const autoGrowTextarea = () => {
        const textarea = textareaRef.current;
        if (textarea) {
            textarea.style.height = 'auto';
            const newHeight = Math.min(textarea.scrollHeight, 200); // Max height: 200px
            textarea.style.height = newHeight + 'px';
        }
    };

    // Handle keyboard shortcuts
    const handleKeyDown = (e) => {
        // Enter without Shift = Send
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (body.trim() && !loading) {
                handleSubmit(e);
            }
            return;
        }
        // Shift+Enter = New line (default behavior, don't prevent)
    };

    const handleEmojiClick = (emojiObject) => {
        const emoji = emojiObject.emoji;
        const textarea = textareaRef.current;
        const cursorPosition = textarea.selectionStart;
        const newBody = body.slice(0, cursorPosition) + emoji + body.slice(cursorPosition);
        
        setBody(newBody);
        setShowPicker(false);
        
        // Focus back to textarea and set cursor after emoji
        setTimeout(() => {
            textarea.focus();
            const newCursorPosition = cursorPosition + emoji.length;
            textarea.setSelectionRange(newCursorPosition, newCursorPosition);
        }, 0);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!body.trim()) return;

        setLoading(true);

        try {
            const response = await fetch('/chat/message', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message: body,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                setBody('');
                lastTypingSentRef.current = 0;

                // optimistic UI update
                if (data?.message) {
                    window.dispatchEvent(
                        new CustomEvent('message:sent', {
                            detail: data.message,
                        })
                    );
                }
            } else {
                const error = await response.json();
                alert(error.message || 'Failed to send message');
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        } finally {
            setLoading(false);
        }
    };
    

    return (
        <form onSubmit={handleSubmit} className="bg-gray-300 p-4">
            <div className="flex gap-2 items-center relative">
                {/* Emoji Picker Button */}
                <button
                    type="button"
                    onClick={() => setShowPicker(!showPicker)}
                    className="text-2xl hover:bg-gray-200 rounded px-2 transition-colors flex-shrink-0"
                    disabled={loading}
                    title="Add emoji"
                >
                    😊
                </button>

                {/* Emoji Picker */}
                {showPicker && (
                    <div ref={pickerRef} className="absolute bottom-full left-0 mb-2 z-50">
                        <EmojiPicker
                            onEmojiClick={handleEmojiClick}
                            width={350}
                            height={400}
                        />
                    </div>
                )}

                <input 
                    ref={textareaRef}
                    type="text"
                    value={body}
                    onChange={handleChange}
                    onKeyDown={handleKeyDown}
                    disabled={loading}
                    placeholder="Type your message…"
                    className="flex items-center h-10 flex-1 rounded px-3 text-sm"
                />

                <button
                    type="submit"
                    disabled={!body.trim() || loading}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
                    title={loading ? 'Sending...' : 'Send (Enter)'}
                >
                    {loading ? '...' : '➤'}
                </button>
            </div>
        </form>
    );
}
