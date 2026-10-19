/**
 * Format a Date object into a `YYYY-MM-DDTHH:MM` string using the
 * browser's local timezone.
 */
export function formatLocalDatetime(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export function localDatetimeToIso(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

function toDateKey(date: Date): string {
    return formatLocalDatetime(date).slice(0, 10);
}

export function parseDateKey(key: string): Date {
    const [year, month, day] = key.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function addDaysToKey(key: string, days: number): string {
    const date = parseDateKey(key);
    date.setDate(date.getDate() + days);

    return toDateKey(date);
}

export function startOfWeekKey(key: string): string {
    const weekday = (parseDateKey(key).getDay() + 6) % 7;

    return addDaysToKey(key, -weekday);
}
