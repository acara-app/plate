import type { MealType } from '@/types/meal-plan';
import { Apple, Coffee, Moon, SunMedium, type LucideIcon } from 'lucide-react';

export interface MealTypeConfig {
    key: MealType;
    Icon: LucideIcon;
    labelKey: string;
    color: string;
    iconColor: string;
}

export const MEAL_TYPES: MealTypeConfig[] = [
    {
        key: 'breakfast',
        Icon: Coffee,
        labelKey: 'meal_plans.meal_types.breakfast',
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-200',
        iconColor: 'text-orange-700 dark:text-orange-300',
    },
    {
        key: 'lunch',
        Icon: SunMedium,
        labelKey: 'meal_plans.meal_types.lunch',
        color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-200',
        iconColor: 'text-cyan-700 dark:text-cyan-300',
    },
    {
        key: 'dinner',
        Icon: Moon,
        labelKey: 'meal_plans.meal_types.dinner',
        color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-200',
        iconColor: 'text-indigo-700 dark:text-indigo-300',
    },
    {
        key: 'snack',
        Icon: Apple,
        labelKey: 'meal_plans.meal_types.snack',
        color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
        iconColor: 'text-emerald-700 dark:text-emerald-300',
    },
];

export function getMealTypeConfig(key: MealType): MealTypeConfig {
    return MEAL_TYPES.find((type) => type.key === key) ?? MEAL_TYPES[0];
}

export function mealTypeForTime(date: Date): MealType {
    const hour = date.getHours();

    if (hour >= 4 && hour < 11) {
        return 'breakfast';
    }

    if (hour >= 11 && hour < 16) {
        return 'lunch';
    }

    if (hour >= 16 && hour < 22) {
        return 'dinner';
    }

    return 'snack';
}
