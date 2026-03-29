import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { I18nextProvider } from 'react-i18next';
import { registerSW } from 'virtual:pwa-register';
import { initializeTheme } from './hooks/use-appearance';
import i18n, { loadTranslations } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Acara Plate';

if (typeof window !== 'undefined') {
    registerSW({ immediate: true });

    const appEl = document.getElementById('app');
    if (appEl?.dataset.page) {
        const page = JSON.parse(appEl.dataset.page);
        loadTranslations(
            (page.props?.locale as string) || 'en',
            (page.props?.translations as Record<string, unknown>) || {},
        );
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    withApp(app) {
        return <I18nextProvider i18n={i18n}>{app}</I18nextProvider>;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
if (typeof window !== 'undefined') {
    initializeTheme();
}
