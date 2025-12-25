export interface GlucoseAnalysisData {
    hasData: boolean;
    totalReadings: number;
    daysAnalyzed: number;
    dateRange: {
        start: string;
        end: string;
    };
    averages: {
        fasting: number | null;
        beforeMeal: number | null;
        postMeal: number | null;
        random: number | null;
        overall: number | null;
    };
    ranges: {
        min: number;
        max: number;
    };
    timeInRange: {
        percentage: number;
        abovePercentage: number;
        belowPercentage: number;
        inRangeCount: number;
        aboveRangeCount: number;
        belowRangeCount: number;
    };
    variability: {
        stdDev: number;
        coefficientOfVariation: number;
        classification: string;
    };
    trend: {
        slopePerDay: number | null;
        slopePerWeek: number | null;
        direction: string | null;
        firstValue: number | null;
        lastValue: number | null;
    };
    timeOfDay: {
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
    readingTypes: Array<{
        type: string;
        count: number;
        average: number | null;
        percentage: number;
    }>;
    patterns: {
        consistentlyHigh: boolean;
        consistentlyLow: boolean;
        highVariability: boolean;
        postMealSpikes: boolean;
        hypoglycemiaRisk: string;
        hyperglycemiaRisk: string;
    };
    insights: string[];
    concerns: string[];
    glucoseGoals: {
        targetRange: {
            min: number;
            max: number;
        };
        fastingTarget: {
            min: number;
            max: number;
        };
        postMealTarget: {
            max: number;
        };
    };
}
