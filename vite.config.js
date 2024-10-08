// See: https://dev.to/robertobutti/laravel-artisan-serve-and-https-cb0

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// 001 import the basicSsl Vite plugin
import basicSsl from '@vitejs/plugin-basic-ssl'

// 002 set the Laravel host and the port
const host = '127.0.0.1';
const port = '8000';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js',
                'resources/sass/sep12_demo.scss', 'resources/js/sep12_demo.js'],
            refresh: true,
        }),
        // 003 load the basicSsl plugin
        basicSsl()
    ],
    // 004 set the server section
    server: {
        // 005 enabling the HTTPS
        https: true,
        // 006 setting the proxy with Laravel as target (origin)
        proxy: {
            '^(?!(\/\@vite|\/resources|\/node_modules))': {
                target: `http://${host}:${port}`,
            }
        },
        host,
        port: 5173,
        // 007 be sure that you have the Hot Module Replacement
        hmr: { host },
    }
});