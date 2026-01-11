// Add this to your browser console for manual Echo testing
// Usage: Open chat page, copy/paste into console

const EchoDebugger = {
    // Test connection status
    testConnection() {
        console.group('🔍 Echo Connection Test');
        if (!window.Echo) {
            console.error('❌ window.Echo not found - check if app.jsx loaded');
            console.groupEnd();
            return;
        }
        console.log('✅ Echo available');
        console.log('Broadcaster:', window.Echo.options.broadcaster);
        console.log('Host:', window.Echo.options.wsHost);
        console.log('Port:', window.Echo.options.wsPort);
        console.log('Key:', window.Echo.options.key);
        console.groupEnd();
    },

    // Test private channel subscription
    testChannelSubscription(conversationId = 5) {
        console.group(`🔌 Testing Channel Subscription: chat.${conversationId}`);
        
        const channel = window.Echo.private(`chat.${conversationId}`);
        
        // Success handler
        channel.subscribed(() => {
            console.log(`✔️ Successfully subscribed to chat.${conversationId}`);
        });

        // Error handler
        channel.error((error) => {
            console.error(`❌ Channel error:`, error);
            if (error.status === 403) {
                console.error('   → Authorization failed - check if user is participant');
            }
        });

        // Listen for messages
        channel.listen('MessageSent', (data) => {
            console.log('📩 Received MessageSent event:', data);
        });

        console.log(`Listening for events on chat.${conversationId}...`);
        console.groupEnd();
        
        return channel;
    },

    // Test channel authorization directly
    async testChannelAuth(conversationId = 5) {
        console.group('🔐 Testing Channel Authorization');
        
        try {
            const response = await fetch(`/broadcasting/auth`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    channel_name: `private-chat.${conversationId}`,
                }),
            });

            console.log('Response Status:', response.status);
            const data = await response.json();
            console.log('Auth Response:', data);
            
            if (response.ok) {
                console.log('✅ Authorization successful');
            } else {
                console.error('❌ Authorization failed');
            }
        } catch (error) {
            console.error('❌ Error testing auth:', error);
        }
        console.groupEnd();
    },

    // Monitor all events on a channel
    monitorChannel(conversationId = 5) {
        console.group(`👁️ Monitoring chat.${conversationId}`);
        
        const channel = window.Echo.private(`chat.${conversationId}`);

        // Log all lifecycle events
        channel.subscribed(() => console.log('📌 Subscribed'));
        channel.error((error) => console.error('📌 Error:', error));
        channel.listen('.', (event, data) => {
            console.log('📌 Caught event:', { event, data });
        });

        // Listen for specific events
        [
            'MessageSent',
            'message',
            'Message',
            'MessageReceived',
        ].forEach(eventName => {
            channel.listen(eventName, (data) => {
                console.log(`📌 Event "${eventName}":`, data);
            });
        });

        console.log('Monitor started - watch for incoming events');
        console.groupEnd();
    },

    // Get debug info from the page
    getDebugInfo() {
        console.group('📊 Debug Information');
        
        const chatAppElement = document.getElementById('chat-app');
        if (chatAppElement) {
            console.log('Chat App Element Found:');
            console.log('  Conversation ID:', chatAppElement.dataset.conversationId);
            console.log('  Current User ID:', chatAppElement.dataset.currentUserId);
            console.log('  Messages Count:', chatAppElement.dataset.initialMessages ? 
                JSON.parse(chatAppElement.dataset.initialMessages).length : 0);
        } else {
            console.error('❌ #chat-app element not found');
        }

        if (window.Echo) {
            console.log('\nEcho Config:');
            console.log('  Broadcaster:', window.Echo.options.broadcaster);
            console.log('  Protocol:', window.Echo.options.forceTLS ? 'WSS' : 'WS');
            console.log('  URL:', `${window.Echo.options.forceTLS ? 'wss' : 'ws'}://${window.Echo.options.wsHost}:${window.Echo.options.wsPort}`);
        }

        console.groupEnd();
    },

    // Simulate sending a message (for testing)
    async simulateMessageSend() {
        console.group('🧪 Simulating Message Send');
        
        const chatApp = document.getElementById('chat-app');
        if (!chatApp) {
            console.error('❌ Chat app not found');
            console.groupEnd();
            return;
        }

        const conversationId = chatApp.dataset.conversationId;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const testMessage = `Test message ${Date.now()}`;

        console.log('Sending:', {
            url: '/chat/message',
            conversation_id: conversationId,
            message: testMessage,
        });

        try {
            const response = await fetch('/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message: testMessage,
                }),
            });

            const data = await response.json();
            console.log('Response:', {
                status: response.status,
                ok: response.ok,
                data: data,
            });

            if (!response.ok) {
                console.error('❌ Send failed');
            } else {
                console.log('✅ Message sent successfully');
            }
        } catch (error) {
            console.error('❌ Error:', error);
        }

        console.groupEnd();
    },

    // Run all tests
    runAllTests(conversationId = 5) {
        console.clear();
        console.log('%c🧪 Echo Debugger - Full Test Suite', 'font-size: 16px; font-weight: bold; color: #00ff00;');
        console.log('');
        
        this.getDebugInfo();
        console.log('');
        
        this.testConnection();
        console.log('');
        
        this.testChannelSubscription(conversationId);
        console.log('');
        
        this.testChannelAuth(conversationId);
    },
};

// Quick test helper
const echoTest = (conversationId = 5) => EchoDebugger.testChannelSubscription(conversationId);
const echoDebug = () => EchoDebugger.getDebugInfo();
const echoMonitor = (conversationId = 5) => EchoDebugger.monitorChannel(conversationId);
const echoSim = () => EchoDebugger.simulateMessageSend();
const echoFull = () => EchoDebugger.runAllTests();

console.log('%c✅ Echo Debugger loaded', 'color: #00ff00; font-weight: bold;');
console.log('Available commands:');
console.log('  echoDebug()           - Show current Echo config');
console.log('  echoTest(id)          - Test subscription to conversation');
console.log('  echoMonitor(id)       - Monitor all events');
console.log('  echoSim()             - Simulate sending message');
console.log('  echoFull()            - Run all tests');
console.log('');
console.log('Example: echoTest(5) - test channel chat.5');
