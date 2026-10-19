import type { HealthEntry } from '@/types/diabetes';
import {
    Droplet,
    Drumstick,
    Flame,
    Salad,
    Wheat,
    type LucideIcon,
} from 'lucide-react';

export type MacroKey = 'protein' | 'carbs' | 'fat' | 'fiber';

export type NutritionField =
    'calories' | 'protein_grams' | 'carbs_grams' | 'fat_grams' | 'fiber_grams';

export type Nutrition = Pick<HealthEntry, NutritionField>;

export interface MacroConfig {
    key: MacroKey;
    field: Exclude<NutritionField, 'calories'>;
    Icon: LucideIcon;
    labelKey: string;
    tile: string;
    card: string;
    icon: string;
    text: string;
    ring: string;
}

export const CALORIES = {
    Icon: Flame,
    icon: 'text-orange-500 dark:text-orange-400',
    ring: 'stroke-primary',
};

export const MACROS: MacroConfig[] = [
    {
        key: 'protein',
        field: 'protein_grams',
        Icon: Drumstick,
        labelKey: 'health_entries.nutrition.protein',
        tile: 'bg-rose-100 dark:bg-rose-500/15',
        card: 'bg-rose-50 dark:bg-rose-500/10',
        icon: 'text-rose-500 dark:text-rose-300',
        text: 'text-rose-600 dark:text-rose-300',
        ring: 'stroke-rose-500 dark:stroke-rose-400',
    },
    {
        key: 'carbs',
        field: 'carbs_grams',
        Icon: Wheat,
        labelKey: 'health_entries.nutrition.carbs',
        tile: 'bg-amber-100 dark:bg-amber-500/15',
        card: 'bg-amber-50 dark:bg-amber-500/10',
        icon: 'text-amber-500 dark:text-amber-300',
        text: 'text-amber-700 dark:text-amber-300',
        ring: 'stroke-amber-400 dark:stroke-amber-300',
    },
    {
        key: 'fat',
        field: 'fat_grams',
        Icon: Droplet,
        labelKey: 'health_entries.nutrition.fat',
        tile: 'bg-orange-100 dark:bg-orange-500/15',
        card: 'bg-orange-50 dark:bg-orange-500/10',
        icon: 'text-orange-500 dark:text-orange-300',
        text: 'text-orange-700 dark:text-orange-300',
        ring: 'stroke-orange-400 dark:stroke-orange-300',
    },
    {
        key: 'fiber',
        field: 'fiber_grams',
        Icon: Salad,
        labelKey: 'health_entries.nutrition.fiber',
        tile: 'bg-green-100 dark:bg-green-500/15',
        card: 'bg-green-50 dark:bg-green-500/10',
        icon: 'text-green-600 dark:text-green-300',
        text: 'text-green-700 dark:text-green-300',
        ring: 'stroke-green-500 dark:stroke-green-400',
    },
];

const formatters = new Map<string, Intl.NumberFormat>();

export function formatAmount(
    value: number,
    locale: string | undefined,
    fractionDigits = 0,
): string {
    const key = `${locale ?? ''}|${fractionDigits}`;
    let formatter = formatters.get(key);

    if (formatter === undefined) {
        formatter = new Intl.NumberFormat(locale, {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: Math.max(fractionDigits, 1),
            useGrouping: 'min2',
        });
        formatters.set(key, formatter);
    }

    return formatter.format(value);
}

export function sumNutrition(entries: Nutrition[]): Nutrition {
    return entries.reduce<Nutrition>(
        (total, entry) => ({
            calories: (total.calories ?? 0) + (entry.calories ?? 0),
            protein_grams:
                (total.protein_grams ?? 0) + (entry.protein_grams ?? 0),
            carbs_grams: (total.carbs_grams ?? 0) + (entry.carbs_grams ?? 0),
            fat_grams: (total.fat_grams ?? 0) + (entry.fat_grams ?? 0),
            fiber_grams: (total.fiber_grams ?? 0) + (entry.fiber_grams ?? 0),
        }),
        {
            calories: 0,
            protein_grams: 0,
            carbs_grams: 0,
            fat_grams: 0,
            fiber_grams: 0,
        },
    );
}
