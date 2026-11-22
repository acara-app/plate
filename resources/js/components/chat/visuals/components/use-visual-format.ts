import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

export function useVisualFormat() {
    const { t, i18n } = useTranslation('visuals');
    const locale = i18n.resolvedLanguage ?? i18n.language;

    return useMemo(() => {
        const numbers = new Intl.NumberFormat(locale, {
            maximumFractionDigits: 2,
        });
        const sentence = (text: string): string =>
            text.charAt(0).toLocaleUpperCase(locale) + text.slice(1);

        return {
            number: (value: number): string => numbers.format(value),
            quantity: (value: number | string): string =>
                typeof value === 'number' ? numbers.format(value) : value,
            unit: (unit: string | null | undefined): string =>
                unit ? t(`units.${unit}`, { defaultValue: unit }) : '',
            label: (key: string): string => sentence(t(key)),
        };
    }, [locale, t]);
}
