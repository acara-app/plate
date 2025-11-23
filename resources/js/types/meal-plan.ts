export type MealType = 'breakfast' | 'lunch' | 'dinner' | 'snack';

export type MealPlanType = 'weekly' | 'monthly' | 'custom';

export interface MacroPercentages {
    protein: number;
    carbs: number;
    fat: number;
}

export interface MacronutrientRatios {
    protein: number;
    carbs: number;
    fat: number;
}

export interface Ingredient {
    name: string;
    quantity: string;
    specificity?: string | null;
    barcode?: string | null;
}

export interface Meal {
    id: number;
    type: MealType;
    name: string;
    description: string | null;
    preparation_instructions: string | null;
    ingredients: Ingredient[] | null;
    portion_size: string | null;
    calories: number;
    protein_grams: number | null;
    carbs_grams: number | null;
    fat_grams: number | null;
    preparation_time_minutes: number | null;
    macro_percentages: MacroPercentages;
}

export interface DailyStats {
    total_calories: number;
    protein: number;
    carbs: number;
    fat: number;
}

export interface CurrentDay {
    day_number: number;
    day_name: string;
    meals: Meal[];
    daily_stats: DailyStats;
}

export interface MealPlan {
    id: number;
    name: string | null;
    description: string | null;
    type: MealPlanType;
    duration_days: number;
    target_daily_calories: number | null;
    macronutrient_ratios: MacronutrientRatios | null;
    metadata: {
        preparation_notes?: string;
        [key: string]: unknown;
    } | null;
    created_at: string;
}

export interface Navigation {
    has_previous: boolean;
    has_next: boolean;
    previous_day: number;
    next_day: number;
    total_days: number;
}
