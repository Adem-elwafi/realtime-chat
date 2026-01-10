// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    define: {
        // Reverb settings for frontend (dev only)
        'import.meta.env.VITE_REVERB_APP_KEY': JSON.stringify('secret-key'),
        'import.meta.env.VITE_REVERB_HOST': JSON.stringify('localhost'),
        'import.meta.env.VITE_REVERB_PORT': JSON.stringify('8080'),
        'import.meta.env.VITE_REVERB_SCHEME': JSON.stringify('http'),
    }
});