import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import { I18nextProvider } from 'react-i18next';
import i18n, { loadTranslations } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Acara Plate';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        setup: ({ App, props }) => {
            const locale = (props.initialPage.props.locale as string) || 'en';
            const translations =
                (props.initialPage.props.translations as Record<
                    string,
                    unknown
                >) || {};
            loadTranslations(locale, translations);

            return (
                <I18nextProvider i18n={i18n}>
                    <App {...props} />
                </I18nextProvider>
            );
        },
    }),
);
