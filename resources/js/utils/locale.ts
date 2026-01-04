import i18n from '@/i18n';

/**
 * Available locales in the application
 */
export const availableLocales = [
    { code: 'en', name: 'English', nativeName: 'English' },
    { code: 'mn', name: 'Mongolian', nativeName: 'Монгол' },
] as const;

export type LocaleCode = (typeof availableLocales)[number]['code'];

/**
 * Switch the application locale
 * @param locale - The locale code to switch to
 */
export const switchLocale = async (locale: LocaleCode): Promise<void> => {
    await i18n.changeLanguage(locale);

    // Store preference in localStorage
    localStorage.setItem('locale', locale);

    // Update document lang attribute for accessibility
    document.documentElement.lang = locale;
};

/**
 * Get the current locale
 */
export const getCurrentLocale = (): string => {
    return i18n.language;
};

/**
 * Get the stored locale preference or default
 */
export const getStoredLocale = (): LocaleCode => {
    const stored = localStorage.getItem('locale');
    return (stored as LocaleCode) || 'en';
};
