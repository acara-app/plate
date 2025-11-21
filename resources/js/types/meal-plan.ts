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

export interface OpenFoodFactsVerification {
    verified: boolean;
    verification_rate: number;
    confidence: 'low' | 'medium' | 'high';
    source: 'ai_estimate' | 'openfoodfacts_verified';
    note?: string;
    original_ai_values?: {
        calories: number;
        protein: number;
        carbs: number;
        fat: number;
    };
    verified_values?: {
        calories: number;
        protein: number;
        carbs: number;
        fat: number;
    };
    corrections_applied?: Record<
        string,
        {
            original: number;
            verified: number;
            corrected: number;
            discrepancy_percent: number;
        }
    >;
    verified_ingredients?: Array<{
        name: string;
        quantity: string | null;
        nutrition_per_100g: {
            calories: number | null;
            protein: number | null;
            carbs: number | null;
            fat: number | null;
            fiber: number | null;
            sugar: number | null;
            sodium: number | null;
        } | null;
        matched: boolean;
    }>;
}

export interface Meal {
    id: number;
    type: MealType;
    name: string;
    description: string | null;
    preparation_instructions: string | null;
    ingredients: string | null;
    portion_size: string | null;
    calories: number;
    protein_grams: number | null;
    carbs_grams: number | null;
    fat_grams: number | null;
    preparation_time_minutes: number | null;
    macro_percentages: MacroPercentages;
    verification_metadata: OpenFoodFactsVerification | null;
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
