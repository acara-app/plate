import type { SleepQuality, StressLevel } from '@/types/diabetes';
import {
    CircleEqual,
    CloudLightning,
    CloudSun,
    Flame,
    Heart,
    Lightbulb,
    Moon,
    Sun,
    type LucideIcon,
} from 'lucide-react';

export type CheckInStep = 'hours' | 'quality' | 'stress';

export const DEFAULT_SLEEP_HOURS = 7.5;

export const MAX_SLEEP_HOURS = 12;

export interface Tone {
    tile: string;
    icon: string;
}

export interface CheckInOption<T> extends Tone {
    value: T;
    Icon: LucideIcon;
    labelKey: string;
}

export interface CheckInStepConfig extends Tone {
    Icon: LucideIcon;
    titleKey: string;
    descriptionKey: string;
}

const ROSE: Tone = {
    tile: 'bg-rose-100 dark:bg-rose-500/15',
    icon: 'text-rose-500 dark:text-rose-300',
};

const AMBER: Tone = {
    tile: 'bg-amber-100 dark:bg-amber-500/15',
    icon: 'text-amber-500 dark:text-amber-300',
};

const GREEN: Tone = {
    tile: 'bg-green-100 dark:bg-green-500/15',
    icon: 'text-green-600 dark:text-green-300',
};

export const SLEEP_TONE: Tone = {
    tile: 'bg-sky-100 dark:bg-sky-500/15',
    icon: 'text-sky-500 dark:text-sky-300',
};

export const STRESS_TONE: Tone = {
    tile: 'bg-orange-100 dark:bg-orange-500/15',
    icon: 'text-orange-400 dark:text-orange-300',
};

export const ACTIVITY_TONE = {
    icon: 'text-green-500 dark:text-green-400',
    indicator: 'bg-green-500 dark:bg-green-400',
};

export const WATER_CUP_ML = 250;

export const MAX_WATER_ML = 5000;

export const WATER_TONE = {
    icon: 'text-blue-500 dark:text-blue-400',
    indicator: 'bg-blue-500 dark:bg-blue-400',
    track: 'bg-blue-100 dark:bg-blue-500/15',
    cup: 'bg-blue-500 text-white ring-blue-500 dark:bg-blue-500',
};

export const CHECK_IN_STEPS: Record<CheckInStep, CheckInStepConfig> = {
    hours: {
        Icon: Moon,
        titleKey: 'health_entries.wellness.hours_title',
        descriptionKey: 'health_entries.wellness.hours_description',
        ...SLEEP_TONE,
    },
    quality: {
        Icon: Moon,
        titleKey: 'health_entries.wellness.quality_title',
        descriptionKey: 'health_entries.wellness.quality_description',
        ...SLEEP_TONE,
    },
    stress: {
        Icon: Lightbulb,
        titleKey: 'health_entries.wellness.stress_title',
        descriptionKey: 'health_entries.wellness.stress_description',
        ...STRESS_TONE,
    },
};

export const SLEEP_QUALITY_OPTIONS: CheckInOption<SleepQuality>[] = [
    {
        value: 1,
        Icon: CloudLightning,
        labelKey: 'health_entries.wellness.quality_poor',
        ...ROSE,
    },
    {
        value: 2,
        Icon: CloudSun,
        labelKey: 'health_entries.wellness.quality_fair',
        ...AMBER,
    },
    {
        value: 3,
        Icon: Sun,
        labelKey: 'health_entries.wellness.quality_good',
        ...GREEN,
    },
];

export const STRESS_OPTIONS: CheckInOption<StressLevel>[] = [
    {
        value: 3,
        Icon: Flame,
        labelKey: 'health_entries.wellness.stress_high',
        ...ROSE,
    },
    {
        value: 2,
        Icon: CircleEqual,
        labelKey: 'health_entries.wellness.stress_moderate',
        ...AMBER,
    },
    {
        value: 1,
        Icon: Heart,
        labelKey: 'health_entries.wellness.stress_calm',
        ...GREEN,
    },
];
