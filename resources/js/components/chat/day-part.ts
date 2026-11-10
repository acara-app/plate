export type DayPart = 'morning' | 'midday' | 'evening' | 'night';

export function dayPartAt(hour: number): DayPart {
    if (hour >= 5 && hour < 11) {
        return 'morning';
    }

    if (hour >= 11 && hour < 16) {
        return 'midday';
    }

    if (hour >= 16 && hour < 21) {
        return 'evening';
    }

    return 'night';
}

export function currentDayPart(): DayPart {
    return dayPartAt(new Date().getHours());
}

export const DAYLIGHT: Record<DayPart, string> = {
    morning:
        'from-amber-200/70 via-rose-100/45 dark:from-amber-400/20 dark:via-rose-400/10',
    midday: 'from-emerald-200/65 via-sky-100/45 dark:from-emerald-400/20 dark:via-sky-400/10',
    evening:
        'from-orange-200/70 via-pink-100/45 dark:from-orange-400/20 dark:via-pink-400/10',
    night: 'from-indigo-200/70 via-sky-100/45 dark:from-indigo-400/25 dark:via-sky-400/10',
};
