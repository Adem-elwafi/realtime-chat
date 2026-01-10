// resources/js/app.js

import './bootstrap'; // if you have it (for Axios/CSRF)
import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatMessages from './components/ChatMessages';
import MessageForm from './components/MessageForm';

// Find the mount point in Blade
const chatApp = document.getElementById('chat-app');

if (chatApp) {
    // Parse data passed from Blade
    const conversationId = parseInt(chatApp.dataset.conversationId);
    const initialMessages = JSON.parse(chatApp.dataset.initialMessages);
    const currentUserId = parseInt(chatApp.dataset.currentUserId);

    // Create React root and render
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