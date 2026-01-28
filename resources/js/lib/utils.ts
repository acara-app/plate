import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

import {
    GlucoseUnit,
    type GlucoseUnitType,
    MGDL_TO_MMOL_FACTOR,
} from '@/types/diabetes';

/**
 * Convert glucose value from mg/dL to the target unit.
 *
 * @param valueMgDl - The glucose value in mg/dL
 * @param targetUnit - The target unit to convert to (mg/dL or mmol/L)
 *
 * @returns The converted value rounded to 1 decimal place for mmol/L or nearest integer for mg/dL
 */
export function convertGlucoseValue(
    valueMgDl: number,
    targetUnit: GlucoseUnitType,
): number {
    if (targetUnit === GlucoseUnit.MmolL) {
        return Math.round((valueMgDl / MGDL_TO_MMOL_FACTOR) * 10) / 10;
    }
    return Math.round(valueMgDl);
}

/**
 * Generates a RFC 4122 compliant UUID v4
 *
 * Uses `crypto.randomUUID()` when available, otherwise falls back to secure
 * random bytes (when `crypto.getRandomValues` exists), and lastly to a
 * non-cryptographic fallback.
 *
 */
export function generateUUID(): string {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }

    // Browser with crypto.getRandomValues or Node.js with global crypto
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);

        // Set version (4) and variant (RFC 4122) bits
        bytes[6] = (bytes[6] & 0x0f) | 0x40; // version 4
        bytes[8] = (bytes[8] & 0x3f) | 0x80; // variant 10xxxxxx

        return Array.from(bytes)
            .map((b, i) => {
                const hex = b.toString(16).padStart(2, '0');
                if ([4, 6, 8, 10].includes(i)) {
                    return `-${hex}`;
                }
                return hex;
            })
            .join('');
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}
