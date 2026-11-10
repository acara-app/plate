export type DayPart = 'morning' | 'midday' | 'evening' | 'night';

export const DAY_PART_STARTS: { part: DayPart; hour: number }[] = [
    { part: 'morning', hour: 5 },
    { part: 'midday', hour: 11 },
    { part: 'evening', hour: 16 },
    { part: 'night', hour: 21 },
];

export function dayPartAt(hour: number): DayPart {
    const current = DAY_PART_STARTS.filter((entry) => hour >= entry.hour).at(
        -1,
    );

    return current?.part ?? 'night';
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
