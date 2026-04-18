import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { registerSW } from 'virtual:pwa-register';
import { initializeTheme } from './hooks/use-appearance';
import i18n, { loadTranslations, reloadTranslations } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Acara Plate';

if (typeof window !== 'undefined') {
    registerSW({ immediate: true });
}

router.on('success', (event) => {
    const locale = event.detail.page.props.locale as string | undefined;

    if (locale && i18n.language !== locale) {
        void reloadTranslations(locale);
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    setup({ el, App, props }) {
        const locale = (props.initialPage.props.locale as string) || 'en';
        const translations =
            (props.initialPage.props.translations as Record<string, unknown>) ||
            {};
        loadTranslations(locale, translations);

        const app = (
            <I18nextProvider i18n={i18n}>
                <App {...props} />
            </I18nextProvider>
        );

        if (el) {
            createRoot(el).render(app);
            return;
        }

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
if (typeof window !== 'undefined') {
    initializeTheme();
}
