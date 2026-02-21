export const PREFERRED_LANGUAGES = {
    ENGLISH: 'en',
    FRENCH: 'fr',
    MONGOLIAN: 'mn',
} as const;

export type PreferredLanguageCode =
    (typeof PREFERRED_LANGUAGES)[keyof typeof PREFERRED_LANGUAGES];

export const PREFERRED_LANGUAGE_OPTIONS: Array<{
    value: PreferredLanguageCode;
    label: string;
}> = [
    { value: 'en', label: 'English' },
    { value: 'fr', label: 'Français' },
    { value: 'mn', label: 'Монгол' },
];
