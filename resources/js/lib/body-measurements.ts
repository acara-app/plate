export type MeasurementSystem = 'metric' | 'imperial';

export interface FeetAndInches {
    feet: string;
    inches: string;
}

const KILOGRAMS_PER_POUND = 0.45359237;
const CENTIMETERS_PER_INCH = 2.54;
const INCHES_PER_FOOT = 12;

function parseMeasurement(value: string): number | null {
    if (value.trim() === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function formatMeasurement(value: number, decimals: number): string {
    return String(Number(value.toFixed(decimals)));
}

export function isMeasurementSystem(value: string): value is MeasurementSystem {
    return value === 'metric' || value === 'imperial';
}

export function defaultMeasurementSystem(appLocale: string): MeasurementSystem {
    if (typeof navigator === 'undefined') {
        return 'metric';
    }

    return appLocale === 'en' && navigator.language.toLowerCase() === 'en-us'
        ? 'imperial'
        : 'metric';
}

export function kilogramsToPounds(kilograms: string): string {
    const value = parseMeasurement(kilograms);

    return value === null
        ? ''
        : formatMeasurement(value / KILOGRAMS_PER_POUND, 1);
}

export function poundsToKilograms(pounds: string): string {
    const value = parseMeasurement(pounds);

    return value === null
        ? ''
        : formatMeasurement(value * KILOGRAMS_PER_POUND, 2);
}

export function centimetersToFeetAndInches(centimeters: string): FeetAndInches {
    const value = parseMeasurement(centimeters);

    if (value === null) {
        return { feet: '', inches: '' };
    }

    const totalInches = Number((value / CENTIMETERS_PER_INCH).toFixed(1));
    const feet = Math.floor(totalInches / INCHES_PER_FOOT);

    return {
        feet: String(feet),
        inches: formatMeasurement(totalInches - feet * INCHES_PER_FOOT, 1),
    };
}

export function feetAndInchesToCentimeters(
    feet: string,
    inches: string,
): string {
    const feetValue = parseMeasurement(feet);
    const inchesValue = parseMeasurement(inches);

    if (feetValue === null && inchesValue === null) {
        return '';
    }

    const totalInches = (feetValue ?? 0) * INCHES_PER_FOOT + (inchesValue ?? 0);

    return formatMeasurement(totalInches * CENTIMETERS_PER_INCH, 2);
}
