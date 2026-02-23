import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'), // <-- map @ to resources/js
        },
    },
    server: {
        // Bind to all interfaces; avoids EADDRNOTAVAIL when LAN IP changes.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Browser cannot load 0.0.0.0; advertise a valid host for Vite client.
        hmr: {
            host: 'localhost',
        },
    },
});
