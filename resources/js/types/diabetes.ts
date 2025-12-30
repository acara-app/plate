// =============================================================================
// Glucose Unit Types & Constants
// =============================================================================

// Glucose Unit constants to avoid magic strings
export const GlucoseUnit = {
    MgDl: 'mg/dL',
    MmolL: 'mmol/L',
} as const;

export type GlucoseUnitType = (typeof GlucoseUnit)[keyof typeof GlucoseUnit];

// Conversion factor: mg/dL ÷ 18.0182 = mmol/L
export const MGDL_TO_MMOL_FACTOR = 18.0182;

// Threshold configurations per unit
export const GlucoseThresholds = {
    fasting: {
        [GlucoseUnit.MgDl]: {
            low: 70,
            normal: '70-100',
            normalMax: 100,
            high: 140,
        },
        [GlucoseUnit.MmolL]: {
            low: 3.9,
            normal: '3.9-5.6',
            normalMax: 5.6,
            high: 7.8,
        },
    },
    postMeal: {
        [GlucoseUnit.MgDl]: {
            low: 70,
            normal: '<180',
            normalMax: 180,
            high: 200,
        },
        [GlucoseUnit.MmolL]: {
            low: 3.9,
            normal: '<10',
            normalMax: 10.0,
            high: 11.1,
        },
    },
} as const;

// Insulin Type constants (matches PHP enum App\Enums\InsulinType)
export const InsulinType = {
    Basal: 'basal',
    Bolus: 'bolus',
    Mixed: 'mixed',
} as const;

export type InsulinTypeValue = (typeof InsulinType)[keyof typeof InsulinType];

// Log Type constants for tabs
export const LogType = {
    Glucose: 'glucose',
    Food: 'food',
    Insulin: 'insulin',
    Meds: 'meds',
    Vitals: 'vitals',
    Exercise: 'exercise',
} as const;

export type LogTypeValue = (typeof LogType)[keyof typeof LogType];

// =============================================================================
// Glucose Analysis Types
// =============================================================================

export interface GlucoseAnalysisData {
    has_data: boolean;
    total_readings: number;
    days_analyzed: number;
    date_range: {
        start: string;
        end: string;
    };
    averages: {
        fasting: number | null;
        before_meal: number | null;
        post_meal: number | null;
        random: number | null;
        overall: number | null;
    };
    ranges: {
        min: number;
        max: number;
    };
    time_in_range: {
        percentage: number;
        above_percentage: number;
        below_percentage: number;
        in_range_count: number;
        above_range_count: number;
        below_range_count: number;
    };
    variability: {
        std_dev: number;
        coefficient_of_variation: number;
        classification: string;
    };
    trend: {
        slope_per_day: number | null;
        slope_per_week: number | null;
        direction: string | null;
        first_value: number | null;
        last_value: number | null;
    };
    time_of_day: {
        morning: {
            average: number | null;
            count: number;
        };
        afternoon: {
            average: number | null;
            count: number;
        };
        evening: {
            average: number | null;
            count: number;
        };
        night: {
            average: number | null;
            count: number;
        };
    };
    reading_types: Array<{
        type: string;
        count: number;
        average: number | null;
        percentage: number;
    }>;
    patterns: {
        consistently_high: boolean;
        consistently_low: boolean;
        high_variability: boolean;
        post_meal_spikes: boolean;
        hypoglycemia_risk: string;
        hyperglycemia_risk: string;
    };
    insights: string[];
    concerns: string[];
    glucose_goals: {
        target_range: {
            min: number;
            max: number;
        };
        fasting_target: {
            min: number;
            max: number;
        };
        post_meal_target: {
            max: number;
        };
    };
}

// =============================================================================
// Diabetes Log Entry Types
// =============================================================================

export interface DiabetesLogEntry {
    id: number;
    glucose_value: number | null;
    glucose_reading_type: string | null;
    measured_at: string;
    notes: string | null;
    insulin_units: number | null;
    insulin_type: string | null;
    medication_name: string | null;
    medication_dosage: string | null;
    weight: number | null;
    blood_pressure_systolic: number | null;
    blood_pressure_diastolic: number | null;
    a1c_value: number | null;
    carbs_grams: number | null;
    exercise_type: string | null;
    exercise_duration_minutes: number | null;
    created_at: string;
}

export interface ReadingType {
    value: string;
    label: string;
}

export interface RecentMedication {
    name: string;
    dosage: string;
    label: string;
}

export interface RecentInsulin {
    units: number;
    type: string;
    label: string;
}

export interface TodaysMeal {
    id: number;
    name: string;
    type: string;
    carbs: number;
    label: string;
}

// =============================================================================
// Dashboard Summary Statistics Types
// =============================================================================

export interface GlucoseStats {
    count: number;
    avg: number;
    min: number;
    max: number;
}

export interface InsulinStats {
    count: number;
    total: number;
    bolusCount: number;
    basalCount: number;
}

export interface CarbStats {
    count: number;
    total: number;
    uniqueDays: number;
    avgPerDay: number;
}

export interface ExerciseStats {
    count: number;
    totalMinutes: number;
    types: string[];
}

export interface WeightStats {
    count: number;
    latest: number | null;
    previous: number | null;
    trend: 'up' | 'down' | 'stable' | null;
    diff: number | null;
}

export interface BloodPressureStats {
    count: number;
    latestSystolic: number | null;
    latestDiastolic: number | null;
}

export interface MedicationStats {
    count: number;
    uniqueMedications: string[];
}

export interface A1cStats {
    count: number;
    latest: number | null;
}

export interface StreakStats {
    currentStreak: number;
    activeDays: number;
}

export interface DataTypes {
    hasGlucose: boolean;
    hasInsulin: boolean;
    hasCarbs: boolean;
    hasExercise: boolean;
    hasMultipleFactors: boolean;
}

export interface DashboardSummary {
    glucoseStats: GlucoseStats;
    insulinStats: InsulinStats;
    carbStats: CarbStats;
    exerciseStats: ExerciseStats;
    weightStats: WeightStats;
    bpStats: BloodPressureStats;
    medicationStats: MedicationStats;
    a1cStats: A1cStats;
    streakStats: StreakStats;
    dataTypes: DataTypes;
}

// =============================================================================
// Page Props Types
// =============================================================================

export type TimePeriod = '7d' | '30d' | '90d';

export interface DiabetesTrackingPageProps {
    logs: DiabetesLogEntry[];
    timePeriod: TimePeriod;
    summary: DashboardSummary;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: GlucoseUnitType;
    recentMedications: RecentMedication[];
    recentInsulins: RecentInsulin[];
    todaysMeals: TodaysMeal[];
    [key: string]: unknown;
}
