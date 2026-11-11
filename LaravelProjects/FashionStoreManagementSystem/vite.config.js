import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pos.js',
                'resources/js/dashboard.js',
                'resources/js/reports.js',
                'resources/js/product-form.js',
            ],
            refresh: true,
        }),
    ],
});
