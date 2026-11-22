import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

const ISO_DAY = /^\d{4}-\d{2}-\d{2}$/;
const ISO_TIME = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/;

export function useVisualFormat() {
    const { t, i18n } = useTranslation('visuals');
    const locale = i18n.resolvedLanguage ?? i18n.language;

    return useMemo(() => {
        const numbers = new Intl.NumberFormat(locale, {
            maximumFractionDigits: 2,
        });
        const supported =
            Intl.DateTimeFormat.supportedLocalesOf([locale]).length > 0;
        const axisDay = new Intl.DateTimeFormat(locale, {
            month: 'numeric',
            day: 'numeric',
            timeZone: 'UTC',
        });
        const fullDay = new Intl.DateTimeFormat(locale, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            timeZone: 'UTC',
        });
        const time = new Intl.DateTimeFormat(locale, {
            hour: 'numeric',
            minute: '2-digit',
        });
        const fullTime = new Intl.DateTimeFormat(locale, {
            dateStyle: 'medium',
            timeStyle: 'short',
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
            axis: (value: string): string => {
                if (ISO_DAY.test(value)) {
                    return axisDay.format(new Date(`${value}T00:00:00Z`));
                }

                return ISO_TIME.test(value)
                    ? time.format(new Date(value))
                    : value;
            },
            pointLabel: (value: string): string => {
                if (ISO_DAY.test(value)) {
                    return supported
                        ? fullDay.format(new Date(`${value}T00:00:00Z`))
                        : value;
                }

                return ISO_TIME.test(value) && supported
                    ? fullTime.format(new Date(value))
                    : value;
            },
        };
    }, [locale, t]);
}
