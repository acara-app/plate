import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

i18n.use(initReactI18next).init({
    resources: {},
    lng: 'en', // Default language
    fallbackLng: 'en',
    ns: ['auth', 'common', 'validation', 'passwords', 'pagination'],
    defaultNS: 'common',
    interpolation: {
        escapeValue: false,
    },
    react: {
        useSuspense: false, // Disable suspense for SSR compatibility
    },
});

/**
 * Load translations from Laravel into i18next.
 * Called on app initialization with translations from Inertia shared data.
 */
export const loadTranslations = (
    locale: string,
    translations: Record<string, unknown>,
): void => {
    Object.entries(translations).forEach(([namespace, resources]) => {
        i18n.addResourceBundle(locale, namespace, resources, true, true);
    });

    i18n.changeLanguage(locale);
};

export default i18n;
