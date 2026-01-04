import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { registerSW } from 'virtual:pwa-register';
import { initializeTheme } from './hooks/use-appearance';
import i18n, { loadTranslations } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

registerSW({ immediate: true });

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        const locale = (props.initialPage.props.locale as string) || 'en';
        const translations =
            (props.initialPage.props.translations as Record<string, unknown>) ||
            {};
        loadTranslations(locale, translations);

        root.render(
            <I18nextProvider i18n={i18n}>
                <App {...props} />
            </I18nextProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
