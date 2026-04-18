import TranslationController from '@/actions/App/Http/Controllers/TranslationController';
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

/**
 * Fetch translations for a locale on demand and apply them to i18next.
 * Used after the user switches their preferred language, since the shared
 * `translations` prop is sent only once per app boot via `Inertia::once`.
 */
export const reloadTranslations = async (locale: string): Promise<void> => {
    const response = await fetch(TranslationController.url(locale), {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        return;
    }

    loadTranslations(locale, await response.json());
};

export default i18n;
