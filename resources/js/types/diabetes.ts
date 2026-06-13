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
// Diabetes Log Entry Types
// =============================================================================

export interface HealthEntry {
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
    protein_grams: number | null;
    fat_grams: number | null;
    calories: number | null;
    exercise_type: string | null;
    exercise_duration_minutes: number | null;
    source: string | null;
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
    protein?: number;
    fat?: number;
    calories?: number;
    label: string;
}
