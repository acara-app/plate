import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Activity, ArrowDown, ArrowUp, Target } from 'lucide-react';

interface GlucoseReading {
    id: number;
    reading_value: number;
    reading_type: string;
    measured_at: string;
    notes: string | null;
    created_at: string;
}

interface Props {
    readings: GlucoseReading[];
}

interface Statistics {
    average: number;
    highest: number;
    lowest: number;
    timeInRange: number;
    totalReadings: number;
}

const NORMAL_RANGE_MIN = 70;
const NORMAL_RANGE_MAX = 140;

function calculateStatistics(readings: GlucoseReading[]): Statistics {
    if (readings.length === 0) {
        return {
            average: 0,
            highest: 0,
            lowest: 0,
            timeInRange: 0,
            totalReadings: 0,
        };
    }

    const values = readings.map((r) => r.reading_value);
    const sum = values.reduce((acc, val) => acc + val, 0);
    const average = sum / values.length;
    const highest = Math.max(...values);
    const lowest = Math.min(...values);

    const inRangeCount = values.filter(
        (v) => v >= NORMAL_RANGE_MIN && v <= NORMAL_RANGE_MAX,
    ).length;
    const timeInRange = (inRangeCount / values.length) * 100;

    return {
        average: Math.round(average),
        highest: Math.round(highest),
        lowest: Math.round(lowest),
        timeInRange: Math.round(timeInRange),
        totalReadings: readings.length,
    };
}

function getGlucoseLevel(value: number): {
    label: string;
    color: string;
    bgColor: string;
} {
    if (value < NORMAL_RANGE_MIN) {
        return {
            label: 'Low',
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        };
    }
    if (value > NORMAL_RANGE_MAX) {
        return {
            label: 'High',
            color: 'text-red-600',
            bgColor: 'bg-red-50',
        };
    }
    return {
        label: 'Normal',
        color: 'text-green-600',
        bgColor: 'bg-green-50',
    };
}

export default function GlucoseStatistics({ readings }: Props) {
    const stats = calculateStatistics(readings);
    const avgLevel = getGlucoseLevel(stats.average);

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Average Glucose
                    </CardTitle>
                    <Activity className="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.average} mg/dL
                    </div>
                    <p className={`text-xs font-medium ${avgLevel.color} mt-1`}>
                        {avgLevel.label} Range
                    </p>
                    <CardDescription className="mt-1">
                        Based on {stats.totalReadings} reading
                        {stats.totalReadings !== 1 ? 's' : ''}
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Highest Reading
                    </CardTitle>
                    <ArrowUp className="size-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.highest} mg/dL
                    </div>
                    <p
                        className={`text-xs font-medium ${getGlucoseLevel(stats.highest).color} mt-1`}
                    >
                        {getGlucoseLevel(stats.highest).label}
                    </p>
                    <CardDescription className="mt-1">
                        Peak glucose level
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Lowest Reading
                    </CardTitle>
                    <ArrowDown className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.lowest} mg/dL
                    </div>
                    <p
                        className={`text-xs font-medium ${getGlucoseLevel(stats.lowest).color} mt-1`}
                    >
                        {getGlucoseLevel(stats.lowest).label}
                    </p>
                    <CardDescription className="mt-1">
                        Minimum glucose level
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Time in Range
                    </CardTitle>
                    <Target className="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.timeInRange}%
                    </div>
                    <p
                        className={`text-xs font-medium ${
                            stats.timeInRange >= 70
                                ? 'text-green-600'
                                : stats.timeInRange >= 50
                                  ? 'text-orange-600'
                                  : 'text-red-600'
                        } mt-1`}
                    >
                        {stats.timeInRange >= 70
                            ? 'Excellent'
                            : stats.timeInRange >= 50
                              ? 'Good'
                              : 'Needs Improvement'}
                    </p>
                    <CardDescription className="mt-1">
                        Target: 70-140 mg/dL
                    </CardDescription>
                </CardContent>
            </Card>
        </div>
    );
}
