import {
    Droplet,
    Dumbbell,
    Footprints,
    GlassWater,
    HeartPulse,
    Moon,
    Pill,
    Scale,
    Utensils,
    Zap,
    type LucideIcon,
} from 'lucide-react';
import { Children, type ReactNode } from 'react';

const ICONS: Record<string, LucideIcon> = {
    steps: Footprints,
    sleep: Moon,
    glucose: Droplet,
    heart: HeartPulse,
    meal: Utensils,
    water: GlassWater,
    weight: Scale,
    workout: Dumbbell,
    energy: Zap,
    medication: Pill,
};

const EMERALD = {
    text: 'text-emerald-700 dark:text-emerald-400',
    soft: 'bg-emerald-600/10 dark:bg-emerald-400/15',
};

const TINTS: Record<string, { text: string; soft: string }> = {
    brand: EMERALD,
    positive: EMERALD,
    steps: EMERALD,
    caution: {
        text: 'text-amber-700 dark:text-amber-400',
        soft: 'bg-amber-500/15 dark:bg-amber-400/15',
    },
    energy: {
        text: 'text-amber-700 dark:text-amber-400',
        soft: 'bg-amber-500/15 dark:bg-amber-400/15',
    },
    critical: {
        text: 'text-red-700 dark:text-red-400',
        soft: 'bg-red-600/10 dark:bg-red-400/15',
    },
    heart: {
        text: 'text-red-700 dark:text-red-400',
        soft: 'bg-red-600/10 dark:bg-red-400/15',
    },
    info: {
        text: 'text-sky-700 dark:text-sky-400',
        soft: 'bg-sky-600/10 dark:bg-sky-400/15',
    },
    water: {
        text: 'text-sky-700 dark:text-sky-400',
        soft: 'bg-sky-600/10 dark:bg-sky-400/15',
    },
    sleep: {
        text: 'text-indigo-700 dark:text-indigo-400',
        soft: 'bg-indigo-600/10 dark:bg-indigo-400/15',
    },
    glucose: {
        text: 'text-rose-700 dark:text-rose-400',
        soft: 'bg-rose-600/10 dark:bg-rose-400/15',
    },
    meal: {
        text: 'text-orange-700 dark:text-orange-400',
        soft: 'bg-orange-500/15 dark:bg-orange-400/15',
    },
    weight: {
        text: 'text-violet-700 dark:text-violet-400',
        soft: 'bg-violet-600/10 dark:bg-violet-400/15',
    },
    workout: {
        text: 'text-lime-700 dark:text-lime-400',
        soft: 'bg-lime-600/15 dark:bg-lime-400/15',
    },
    medication: {
        text: 'text-fuchsia-700 dark:text-fuchsia-400',
        soft: 'bg-fuchsia-600/10 dark:bg-fuchsia-400/15',
    },
};

const NEUTRAL = { text: 'text-foreground/70', soft: 'bg-foreground/[0.06]' };

export const TONES = {
    info: {
        text: 'text-sky-700 dark:text-sky-400',
        badge: 'bg-sky-600/10 text-sky-800 dark:bg-sky-400/15 dark:text-sky-300',
        surface: 'bg-sky-600/[0.07] dark:bg-sky-400/10',
    },
    positive: {
        text: 'text-emerald-700 dark:text-emerald-400',
        badge: 'bg-emerald-600/10 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300',
        surface: 'bg-emerald-600/[0.07] dark:bg-emerald-400/10',
    },
    caution: {
        text: 'text-amber-700 dark:text-amber-400',
        badge: 'bg-amber-500/15 text-amber-900 dark:bg-amber-400/15 dark:text-amber-300',
        surface: 'bg-amber-500/10 dark:bg-amber-400/10',
    },
    critical: {
        text: 'text-red-700 dark:text-red-400',
        badge: 'bg-red-600/10 text-red-800 dark:bg-red-400/15 dark:text-red-300',
        surface: 'bg-red-600/[0.07] dark:bg-red-400/10',
    },
} as const;

export function visualIcon(name: string | null | undefined): LucideIcon | null {
    return name ? (ICONS[name] ?? null) : null;
}

export function tint(name: string | null | undefined): {
    text: string;
    soft: string;
} {
    return name ? (TINTS[name] ?? NEUTRAL) : NEUTRAL;
}

export function hasChildren(children: ReactNode): boolean {
    return Children.toArray(children).length > 0;
}

export const GAPS = {
    none: 'gap-0',
    sm: 'gap-2',
    md: 'gap-4',
    lg: 'gap-6',
} as const;
