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
