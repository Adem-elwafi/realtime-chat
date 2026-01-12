<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Broadcast Debug Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold mb-4 text-blue-600">🔧 Broadcast Debugging Tool</h1>
            <p class="text-gray-600 mb-4">Use this page to test if your real-time broadcasting is working correctly.</p>
        </div>

        <!-- Configuration Display -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">📡 Current Configuration</h2>
            <div class="space-y-2 font-mono text-sm">
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">BROADCAST_DRIVER:</span>
                    <span class="text-blue-600">{{ config('broadcasting.default') }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Reverb App ID:</span>
                    <span class="text-blue-600">{{ config('broadcasting.connections.reverb.app_id') }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Reverb App Key:</span>
                    <span class="text-blue-600">{{ config('broadcasting.connections.reverb.key') }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Reverb Host:</span>
                    <span class="text-blue-600">{{ config('broadcasting.connections.reverb.options.host') ?? 'localhost' }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Reverb Port:</span>
                    <span class="text-blue-600">{{ config('broadcasting.connections.reverb.options.port') ?? '8080' }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Frontend Vite Key:</span>
                    <span class="text-blue-600">{{ env('VITE_REVERB_APP_KEY') }}</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="font-semibold">Logged In User:</span>
                    <span class="text-blue-600">{{ auth()->user()->name }} (ID: {{ auth()->id() }})</span>
                </div>
            </div>
        </div>

        <!-- Available Conversations -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">💬 Your Conversations</h2>
            @if($conversations->isEmpty())
                <p class="text-gray-500">No conversations found. Create a conversation first!</p>
            @else
                <div class="space-y-2">
                    @foreach($conversations as $conversation)
                        <div class="p-4 bg-gray-50 rounded border border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-semibold">Conversation ID: {{ $conversation->id }}</p>
                                    <p class="text-sm text-gray-600">
                                        Between: User {{ $conversation->user_one_id }} ↔ User {{ $conversation->user_two_id }}
                                    </p>
                                    <p class="text-xs text-gray-500">Channel: <code class="bg-gray-200 px-1 rounded">chat.{{ $conversation->id }}</code></p>
                                </div>
                                <button 
                                    onclick="testBroadcast({{ $conversation->id }})"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                                >
                                    📤 Send Test Broadcast
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Listen Test -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">👂 Listen for Broadcasts</h2>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">Conversation ID to listen to:</label>
                <input 
                    type="number" 
                    id="listenConversationId" 
                    placeholder="Enter conversation ID"
                    class="w-full p-2 border border-gray-300 rounded"
                >
            </div>
            <button 
                onclick="startListening()"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-semibold"
            >
                🎧 Start Listening
            </button>
            <button 
                onclick="stopListening()"
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded font-semibold ml-2"
            >
                ⏹ Stop Listening
            </button>
        </div>

        <!-- Console Log -->
        <div class="bg-gray-900 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-white">📋 Console Log</h2>
            <div id="console-log" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                <div class="mb-1">🚀 Debug console ready...</div>
                <div class="mb-1">💡 Open browser DevTools console for full details</div>
            </div>
        </div>
    </div>

    <script>
        let currentChannel = null;

        function log(message, type = 'info') {
            const consoleLog = document.getElementById('console-log');
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                info: 'text-green-400',
                success: 'text-blue-400',
                error: 'text-red-400',
                warning: 'text-yellow-400'
            };
            const div = document.createElement('div');
            div.className = `mb-1 ${colors[type]}`;
            div.textContent = `[${timestamp}] ${message}`;
            consoleLog.appendChild(div);
            consoleLog.scrollTop = consoleLog.scrollHeight;
            
            // Also log to browser console
            console.log(message);
        }

        function testBroadcast(conversationId) {
            log(`📤 Sending test broadcast to conversation ${conversationId}...`, 'info');
            
            fetch(`/debug/test-broadcast/${conversationId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    log(`✅ ${data.message}`, 'success');
                    log(`📊 Message ID: ${data.message_id}`, 'info');
                    log(`📡 Channel: ${data.channel}`, 'info');
                } else {
                    log(`❌ Error: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                log(`❌ Network error: ${error.message}`, 'error');
                console.error('Full error:', error);
            });
        }

        function startListening() {
            const conversationId = document.getElementById('listenConversationId').value;
            
            if (!conversationId) {
                log('⚠️ Please enter a conversation ID', 'warning');
                return;
            }

            if (currentChannel) {
                log('⚠️ Already listening. Stop first before starting new listener.', 'warning');
                return;
            }

            if (!window.Echo) {
                log('❌ Echo not initialized! Check browser console.', 'error');
                return;
            }

            const channelName = `chat.${conversationId}`;
            log(`🎧 Starting to listen on channel: ${channelName}`, 'info');

            currentChannel = window.Echo.private(channelName);

            currentChannel.subscribed(() => {
                log(`✅ Successfully subscribed to ${channelName}`, 'success');
            });

            currentChannel.error((error) => {
                log(`❌ Subscription error: ${JSON.stringify(error)}`, 'error');
                console.error('Subscription error details:', error);
            });

            currentChannel.listen('MessageSent', (data) => {
                log(`📩 MESSAGE RECEIVED!`, 'success');
                log(`📊 Data: ${JSON.stringify(data)}`, 'info');
                console.log('Full message data:', data);
            });
        }

        function stopListening() {
            if (!currentChannel) {
                log('⚠️ Not currently listening to any channel', 'warning');
                return;
            }

            const channelName = currentChannel.name;
            window.Echo.leave(channelName);
            currentChannel = null;
            log(`⏹ Stopped listening to ${channelName}`, 'info');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            log('✅ Page loaded', 'success');
            
            if (window.Echo) {
                log('✅ Echo instance available', 'success');
                log(`📡 Broadcaster: ${window.Echo.options.broadcaster}`, 'info');
                log(`🔗 WS Host: ${window.Echo.options.wsHost}:${window.Echo.options.wsPort}`, 'info');
            } else {
                log('❌ Echo not available! Check console.', 'error');
            }
        });
    </script>
</body>
</html>
