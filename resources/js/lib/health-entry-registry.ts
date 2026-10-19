import { convertGlucoseValue } from '@/lib/utils';
import {
    FoodBreakdownItem,
    HealthEntry,
    LogType,
    LogTypeValue,
    type GlucoseUnitType,
} from '@/types/diabetes';
import {
    Activity,
    Droplet,
    HeartPulse,
    Pill,
    Syringe,
    Utensils,
    type LucideIcon,
} from 'lucide-react';

type TranslateFn = (key: string, options?: Record<string, unknown>) => string;

export interface HealthEntryTypeConfig {
    key: LogTypeValue;
    icon: LucideIcon;
    labelKey: string;
    accent: string;
}

export const HEALTH_ENTRY_TYPES: HealthEntryTypeConfig[] = [
    {
        key: LogType.Glucose,
        icon: Droplet,
        labelKey: 'health_entries.tabs.glucose',
        accent: 'text-rose-600 dark:text-rose-400',
    },
    {
        key: LogType.Food,
        icon: Utensils,
        labelKey: 'health_entries.tabs.food',
        accent: 'text-amber-600 dark:text-amber-400',
    },
    {
        key: LogType.Insulin,
        icon: Syringe,
        labelKey: 'health_entries.tabs.insulin',
        accent: 'text-blue-600 dark:text-blue-400',
    },
    {
        key: LogType.Meds,
        icon: Pill,
        labelKey: 'health_entries.tabs.meds',
        accent: 'text-violet-600 dark:text-violet-400',
    },
    {
        key: LogType.Vitals,
        icon: HeartPulse,
        labelKey: 'health_entries.tabs.vitals',
        accent: 'text-purple-600 dark:text-purple-400',
    },
    {
        key: LogType.Exercise,
        icon: Activity,
        labelKey: 'health_entries.tabs.exercise',
        accent: 'text-cyan-600 dark:text-cyan-400',
    },
];

export function getTypeConfig(key: LogTypeValue): HealthEntryTypeConfig {
    return (
        HEALTH_ENTRY_TYPES.find((type) => type.key === key) ??
        HEALTH_ENTRY_TYPES[0]
    );
}

export function getPrimaryType(entry: HealthEntry): LogTypeValue {
    if (entry.glucose_value !== null) {
        return LogType.Glucose;
    }
    if (hasFood(entry)) {
        return LogType.Food;
    }
    if (entry.insulin_units !== null) {
        return LogType.Insulin;
    }
    if (entry.medication_name) {
        return LogType.Meds;
    }
    if (
        entry.weight !== null ||
        entry.blood_pressure_systolic !== null ||
        entry.blood_pressure_diastolic !== null ||
        entry.a1c_value !== null
    ) {
        return LogType.Vitals;
    }
    if (entry.exercise_type) {
        return LogType.Exercise;
    }

    return LogType.Glucose;
}

export function hasFood(
    entry: Pick<
        HealthEntry,
        'calories' | 'carbs_grams' | 'protein_grams' | 'fat_grams'
    >,
): boolean {
    return (
        entry.calories !== null ||
        entry.carbs_grams !== null ||
        entry.protein_grams !== null ||
        entry.fat_grams !== null
    );
}

export function mealLabel(items: Pick<FoodBreakdownItem, 'name'>[]): string {
    return items
        .map((item) => item.name.trim())
        .filter((name) => name !== '')
        .join(', ')
        .slice(0, 500);
}

function isAutoMealLabel(
    notes: string | null,
    items: Pick<FoodBreakdownItem, 'name'>[],
): boolean {
    return notes !== null && items.length > 0 && notes === mealLabel(items);
}

export function userNotes(entry: HealthEntry): string | null {
    const items = entry.food_analysis?.items ?? [];

    return isAutoMealLabel(entry.notes, items) ? null : entry.notes;
}

export function mealTitle(
    items: Pick<FoodBreakdownItem, 'name'>[],
    notes: string | null,
    t: TranslateFn,
): string {
    if (items.length > 0) {
        return mealLabel(items);
    }

    return notes?.split('\n')[0].trim() || t('health_entries.timeline.meal');
}

export function entryTitle(entry: HealthEntry, t: TranslateFn): string {
    return hasFood(entry)
        ? mealTitle(entry.food_analysis?.items ?? [], entry.notes, t)
        : t(getTypeConfig(getPrimaryType(entry)).labelKey);
}

export interface EntryBadge {
    key: string;
    label: string;
    subtle?: boolean;
}

export function getEntryBadges(
    entry: HealthEntry,
    glucoseUnit: string,
    t: TranslateFn,
): EntryBadge[] {
    const badges: EntryBadge[] = [];

    if (entry.glucose_value !== null) {
        badges.push({
            key: 'glucose',
            label: `${convertGlucoseValue(entry.glucose_value, glucoseUnit as GlucoseUnitType)} ${glucoseUnit}`,
        });
        if (entry.glucose_reading_type) {
            badges.push({
                key: 'glucose_reading_type',
                label: entry.glucose_reading_type.replaceAll('-', ' '),
                subtle: true,
            });
        }
    }

    if (entry.insulin_units !== null) {
        const type = entry.insulin_type ? ` ${entry.insulin_type}` : '';
        badges.push({
            key: 'insulin',
            label: `${entry.insulin_units}${t('health_entries.index_page.units_label')}${type}`,
        });
    }

    if (entry.medication_name) {
        const dosage = entry.medication_dosage
            ? ` ${entry.medication_dosage}`
            : '';
        badges.push({
            key: 'medication',
            label: `${entry.medication_name}${dosage}`,
        });
    }

    if (entry.weight !== null) {
        badges.push({
            key: 'weight',
            label: `${entry.weight} ${t('health_entries.index_page.kg_label')}`,
        });
    }

    if (
        entry.blood_pressure_systolic !== null ||
        entry.blood_pressure_diastolic !== null
    ) {
        const systolic = entry.blood_pressure_systolic ?? '–';
        const diastolic = entry.blood_pressure_diastolic ?? '–';
        badges.push({
            key: 'bp',
            label: `${systolic}/${diastolic} ${t('health_entries.index_page.bp_label')}`,
        });
    }

    if (entry.a1c_value !== null) {
        badges.push({
            key: 'a1c',
            label: t('health_entries.badges.a1c', { value: entry.a1c_value }),
        });
    }

    if (entry.exercise_type || entry.exercise_duration_minutes !== null) {
        const parts: string[] = [];
        if (entry.exercise_type) {
            parts.push(entry.exercise_type);
        }
        if (entry.exercise_duration_minutes !== null) {
            parts.push(
                t('health_entries.badges.exercise_minutes', {
                    value: entry.exercise_duration_minutes,
                }),
            );
        }
        badges.push({
            key: 'exercise',
            label: parts.join(' · '),
        });
    }

    return badges;
}

export const SOURCE_META: Record<string, { labelKey: string }> = {
    mobile_sync: { labelKey: 'health_entries.source.apple_health' },
    chat: { labelKey: 'health_entries.source.chat' },
    telegram: { labelKey: 'health_entries.source.telegram' },
    web: { labelKey: 'health_entries.source.manual' },
    mobile: { labelKey: 'health_entries.source.mobile' },
};

const READING_BADGE_KEYS: Record<Exclude<LogTypeValue, 'food'>, string[]> = {
    glucose: ['glucose', 'glucose_reading_type'],
    insulin: ['insulin'],
    meds: ['medication'],
    vitals: ['weight', 'bp', 'a1c'],
    exercise: ['exercise'],
};

interface ReadingGroup {
    type: LogTypeValue;
    badges: EntryBadge[];
}

export function getReadingGroups(
    entry: HealthEntry,
    glucoseUnit: string,
    t: TranslateFn,
): ReadingGroup[] {
    const badges = getEntryBadges(entry, glucoseUnit, t);

    return Object.entries(READING_BADGE_KEYS)
        .map(([type, keys]) => ({
            type: type as LogTypeValue,
            badges: badges.filter((badge) => keys.includes(badge.key)),
        }))
        .filter((group) => group.badges.some((badge) => !badge.subtle));
}
