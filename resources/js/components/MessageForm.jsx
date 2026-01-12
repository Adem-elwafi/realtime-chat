// resources/js/components/MessageForm.jsx

import { useState, useRef } from 'react';

export default function MessageForm({ conversationId }) {
    const [body, setBody] = useState('');
    const [loading, setLoading] = useState(false);

    const lastTypingSentRef = useRef(0);
    const typingTimeoutRef = useRef(null);
    const lastTypingRef = useRef(0);
    const stopTypingTimeout = useRef(null);

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
        <form onSubmit={handleSubmit} className="p-4 border-t">
            <div className="flex gap-2">
                <textarea
                    value={body}
                    onChange={handleChange}
                    placeholder="Type a message..."
                    className="flex-1 border rounded p-2 resize-none"
                    rows="1"
                    disabled={loading}
                />
                <button
                    type="submit"
                    disabled={!body.trim() || loading}
                    className="bg-blue-500 text-white px-4 py-2 rounded disabled:opacity-50"
                >
                    {loading ? 'Sending...' : 'Send'}
                </button>
            </div>
        </form>
    );
}
