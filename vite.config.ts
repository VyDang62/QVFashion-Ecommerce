import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/main.css',
                'resources/css/client/tailwind.css',
                'resources/css/client/style.css',
                'resources/css/client/custom.css',
                'resources/js/app.ts',
                'resources/js/customer.ts',
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        wayfinder({
            formVariants: true,
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
    
});
