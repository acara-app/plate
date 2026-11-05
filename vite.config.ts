import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { existsSync, readdirSync, readFileSync, rmSync } from 'node:fs';
import { defineConfig, type Manifest } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

const serviceWorkerFilename = `sw-${Date.now().toString(36)}.js`;

function removeOutdatedServiceWorkers(): void {
    for (const file of readdirSync('public')) {
        if (
            /^sw(-[a-z0-9]+)?\.js(\.map)?$/.test(file) &&
            !file.startsWith(serviceWorkerFilename)
        ) {
            rmSync(`public/${file}`);
        }
    }
}

function appShellUrls(entry: string): Set<string> {
    const manifestPath = 'public/build/manifest.json';
    const urls = new Set<string>();

    if (!existsSync(manifestPath)) {
        return urls;
    }

    const manifest = JSON.parse(
        readFileSync(manifestPath, 'utf-8'),
    ) as Manifest;

    const collect = (key: string): void => {
        const chunk = manifest[key];

        if (!chunk || urls.has(`build/${chunk.file}`)) {
            return;
        }

        urls.add(`build/${chunk.file}`);
        chunk.css?.forEach((file) => urls.add(`build/${file}`));
        chunk.imports?.forEach(collect);
    };

    collect(entry);

    return urls;
}

export default defineConfig({
    resolve: {
        dedupe: ['react', 'react-dom'],
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        inertia(),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        {
            name: 'remove-outdated-service-workers',
            apply: (_, { command, isSsrBuild }) =>
                command === 'build' && !isSsrBuild,
            writeBundle: removeOutdatedServiceWorkers,
        },
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public',
            filename: serviceWorkerFilename,
            buildBase: '/',
            scope: '/',
            workbox: {
                cleanupOutdatedCaches: true,
                directoryIndex: null,
                navigateFallback: null,
                dontCacheBustURLsMatching: /^build\/assets\//,
                manifestTransforms: [
                    (entries) => {
                        const appShell = appShellUrls('resources/js/app.tsx');

                        return {
                            manifest: entries.filter(({ url }) =>
                                appShell.has(url),
                            ),
                        };
                    },
                ],
                runtimeCaching: [
                    {
                        urlPattern: ({ sameOrigin, url }) =>
                            sameOrigin &&
                            url.pathname.startsWith('/build/assets/'),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'build-assets',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
            integration: {
                beforeBuildServiceWorker(options) {
                    options.workbox.additionalManifestEntries =
                        options.workbox.additionalManifestEntries?.map(
                            (entry) =>
                                typeof entry !== 'string' &&
                                entry.url === options.manifestFilename
                                    ? { ...entry, url: `build/${entry.url}` }
                                    : entry,
                        );
                },
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
    oxc: {
        jsx: {
            runtime: 'automatic',
        },
    },
});
