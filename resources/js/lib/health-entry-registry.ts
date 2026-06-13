import { convertGlucoseValue } from '@/lib/utils';
import {
    HealthEntry,
    LogType,
    LogTypeValue,
    type GlucoseUnitType,
} from '@/types/diabetes';
import {
    Activity,
    Droplet,
    HeartPulse,
    MessageCircle,
    Pill,
    Send,
    Smartphone,
    SquarePen,
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
    if (
        entry.carbs_grams !== null ||
        entry.protein_grams !== null ||
        entry.fat_grams !== null ||
        entry.calories !== null
    ) {
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
        entry.a1c_value !== null
    ) {
        return LogType.Vitals;
    }
    if (entry.exercise_type) {
        return LogType.Exercise;
    }

    return LogType.Glucose;
}

const BADGE_ACCENT = {
    glucose: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    carbs: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    protein:
        'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
    fat: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
    calories: 'bg-lime-100 text-lime-700 dark:bg-lime-950 dark:text-lime-300',
    insulin: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    medication:
        'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    weight: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    bp: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
    a1c: 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    exercise: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
} as const;

const MACRO_BADGES = [
    { key: 'carbs', field: 'carbs_grams', accent: BADGE_ACCENT.carbs },
    { key: 'protein', field: 'protein_grams', accent: BADGE_ACCENT.protein },
    { key: 'fat', field: 'fat_grams', accent: BADGE_ACCENT.fat },
    { key: 'calories', field: 'calories', accent: BADGE_ACCENT.calories },
] as const satisfies ReadonlyArray<{
    key: string;
    field: keyof HealthEntry;
    accent: string;
}>;

export interface EntryBadge {
    key: string;
    label: string;
    className?: string;
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
            className: BADGE_ACCENT.glucose,
        });
        if (entry.glucose_reading_type) {
            badges.push({
                key: 'glucose_reading_type',
                label: entry.glucose_reading_type.replace('-', ' '),
                subtle: true,
            });
        }
    }

    for (const macro of MACRO_BADGES) {
        const value = entry[macro.field];
        if (value !== null) {
            badges.push({
                key: macro.key,
                label: t(`health_entries.badges.${macro.key}`, { value }),
                className: macro.accent,
            });
        }
    }

    if (entry.insulin_units !== null) {
        const type = entry.insulin_type ? ` ${entry.insulin_type}` : '';
        badges.push({
            key: 'insulin',
            label: `${entry.insulin_units}${t('health_entries.index_page.units_label')}${type}`,
            className: BADGE_ACCENT.insulin,
        });
    }

    if (entry.medication_name) {
        const dosage = entry.medication_dosage
            ? ` ${entry.medication_dosage}`
            : '';
        badges.push({
            key: 'medication',
            label: `${entry.medication_name}${dosage}`,
            className: BADGE_ACCENT.medication,
        });
    }

    if (entry.weight !== null) {
        badges.push({
            key: 'weight',
            label: `${entry.weight} ${t('health_entries.index_page.kg_label')}`,
            className: BADGE_ACCENT.weight,
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
            className: BADGE_ACCENT.bp,
        });
    }

    if (entry.a1c_value !== null) {
        badges.push({
            key: 'a1c',
            label: t('health_entries.badges.a1c', { value: entry.a1c_value }),
            className: BADGE_ACCENT.a1c,
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
            className: BADGE_ACCENT.exercise,
        });
    }

    return badges;
}

export interface SourceMeta {
    labelKey: string;
    icon: LucideIcon;
}

export const SOURCE_META: Record<string, SourceMeta> = {
    mobile_sync: {
        labelKey: 'health_entries.source.apple_health',
        icon: Smartphone,
    },
    chat: { labelKey: 'health_entries.source.chat', icon: MessageCircle },
    telegram: { labelKey: 'health_entries.source.telegram', icon: Send },
    web: { labelKey: 'health_entries.source.manual', icon: SquarePen },
};
