// resources/js/components/MessageForm.jsx
import { useState } from 'react';

/**
 * Form to send new messages via AJAX.
 * 
 * Props:
 * - conversationId: ID of the current conversation
 */
export default function MessageForm({ conversationId }) {
    const [body, setBody] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!body.trim()) return;

        setLoading(true);

        try {
            const response = await fetch(`/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message: body, // ← matches your StoreMessageRequest
                }),
            });

            if (response.ok) {
                setBody(''); // Clear input
            } else {
                const error = await response.json();
                alert(error.error || 'Failed to send message');
            }
        } catch (err) {
            console.error(err);
            alert('Network error. Is Reverb running?');
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="p-4 border-t">
            <div className="flex gap-2">
                <textarea
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
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