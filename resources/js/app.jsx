// resources/js/app.jsx
import './bootstrap'; // ← Initialize axios and Echo FIRST
import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatMessages from './components/ChatMessages';   // ✅ relative path
import MessageForm from './components/MessageForm';     // ✅

const chatApp = document.getElementById('chat-app');
if (chatApp) {
    const conversationId = parseInt(chatApp.dataset.conversationId);
    const initialMessages = JSON.parse(chatApp.dataset.initialMessages);
    const currentUserId = parseInt(chatApp.dataset.currentUserId);

    const root = createRoot(chatApp);
    root.render(
        <div className="flex flex-col h-[600px]">
            <ChatMessages 
                conversationId={conversationId}
                initialMessages={initialMessages}
                currentUserId={currentUserId}
            />
            <MessageForm conversationId={conversationId} />
        </div>
    );
}