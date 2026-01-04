import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// Import translation files
import authEn from './locales/en/auth.json';
import commonEn from './locales/en/common.json';
import authMn from './locales/mn/auth.json';
import commonMn from './locales/mn/common.json';

// Translation resources
const resources = {
    en: {
        auth: authEn,
        common: commonEn,
    },
    mn: {
        auth: authMn,
        common: commonMn,
    },
};

// Initialize i18next
i18n.use(initReactI18next).init({
    resources,
    lng: 'en', // Default language
    fallbackLng: 'en',
    ns: ['auth', 'common'], // Namespaces
    defaultNS: 'common',
    interpolation: {
        escapeValue: false, // React already escapes values
    },
    react: {
        useSuspense: false, // Disable suspense for SSR compatibility
    },
});

export default i18n;
