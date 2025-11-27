import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            // ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public',
            buildBase: '/',
            scope: '/',
            workbox: {
                cleanupOutdatedCaches: true,
                directoryIndex: null,
                navigateFallback: null,
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
            manifest: {
                name: 'Acara Plate',
                short_name: 'Acara Plate',
                description:
                    'Personalized nutrition and meal planning platform',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/dashboard',
                icons: [
                    {
                        src: '/favicons/favicon-16x16.png',
                        sizes: '16x16',
                        type: 'image/png',
                    },
                    {
                        src: '/favicons/favicon-32x32.png',
                        sizes: '32x32',
                        type: 'image/png',
                    },
                    {
                        src: '/favicons/favicon-48x48.png',
                        sizes: '48x48',
                        type: 'image/png',
                    },
                    {
                        src: '/apple-touch-icon/apple-touch-icon-180x180.png',
                        sizes: '180x180',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                    {
                        src: '/apple-touch-icon/apple-touch-icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/apple-touch-icon/apple-touch-icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                ],
            },
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
