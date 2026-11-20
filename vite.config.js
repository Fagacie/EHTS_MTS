import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inertia.ts',
            ],
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: 'vite-preload',
                            test: /preload-helper/,
                            priority: 40,
                        },
                        {
                            name: 'vendor-vue',
                            test: /node_modules[\\/](?:vue|@vue)[\\/]/,
                            priority: 30,
                        },
                        {
                            name: 'vendor-hls-light',
                            test: /node_modules[\\/]hls\.js[\\/]dist[\\/]hls\.light/,
                            priority: 29,
                        },
                        {
                            name: 'vendor-hls',
                            test: /node_modules[\\/]hls\.js[\\/]/,
                            priority: 28,
                        },
                        {
                            name: 'vendor-charts',
                            test: /node_modules[\\/](?:chart\.js|vue-chartjs|@kurkle)[\\/]/,
                            priority: 26,
                        },
                        {
                            name: 'vendor-lottie',
                            test: /node_modules[\\/]lottie-web[\\/]/,
                            priority: 26,
                        },
                        {
                            name: 'vendor-inertia',
                            test: /node_modules[\\/]@inertiajs[\\/]/,
                            priority: 25,
                        },
                        {
                            name: 'vendor-ui',
                            test: /node_modules[\\/](?:reka-ui|lucide-vue-next|class-variance-authority|clsx|tailwind-merge)[\\/]/,
                            priority: 20,
                        },
                    ],
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
