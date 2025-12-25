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
