// resources/js/echo.js

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally (required by Echo)
window.Pusher = Pusher;

// Create and export an Echo instance configured for Reverb
// Use sensible fallbacks so Echo still connects in local dev when env vars are missing
const host = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const port = import.meta.env.VITE_REVERB_PORT ?? 8081;
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? (window.location.protocol === 'https:' ? 'https' : 'http');
const useTLS = scheme === 'https';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

console.log('🚀 Initializing Laravel Echo...');
console.log('📡 Reverb Configuration:', {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: host,
    port: port,
    scheme: scheme,
    useTLS: useTLS,
    csrfToken: csrfToken ? '✓ Present' : '✗ Missing',
});
console.log('🔑 ACTUAL KEY BEING USED:', import.meta.env.VITE_REVERB_APP_KEY);
console.log('🔑 KEY LENGTH:', import.meta.env.VITE_REVERB_APP_KEY?.length);

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: useTLS,
    withCredentials: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    },
});

console.log('✅ Laravel Echo initialized successfully');
console.log('🌐 WebSocket connection string:', `${useTLS ? 'wss' : 'ws'}://${host}:${port}`);

// Add Pusher connection state monitoring
if (echo.connector && echo.connector.pusher) {
    const pusher = echo.connector.pusher;
    
    pusher.connection.bind('connected', () => {
        console.log('✅ ✅ ✅ PUSHER CONNECTED!');
    });
    
    pusher.connection.bind('connecting', () => {
        console.log('🔄 Pusher connecting...');
    });
    
    pusher.connection.bind('unavailable', () => {
        console.error('❌ Pusher unavailable!');
    });
    
    pusher.connection.bind('failed', () => {
        console.error('❌ ❌ ❌ PUSHER CONNECTION FAILED!');
    });
    
    pusher.connection.bind('disconnected', () => {
        console.warn('⚠️ Pusher disconnected');
    });
    
    pusher.connection.bind('error', (error) => {
        console.error('❌ Pusher connection error:', error);
        console.error('❌ Error type:', error.type);
        console.error('❌ Error data:', error.error);
        console.error('❌ Trying to connect to:', `${useTLS ? 'wss' : 'ws'}://${host}:${port}`);
        console.error('❌ Check: 1) Is Reverb running? 2) Is port 8081 open? 3) Is the URL correct?');
    });
}

// Make Echo available globally for debugging
window.Echo = echo;

export default echo;