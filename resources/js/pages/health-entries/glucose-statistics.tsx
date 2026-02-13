import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Activity, ArrowDown, ArrowUp, Target } from 'lucide-react';
import { useTranslation } from 'react-i18next';

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

function getGlucoseLevel(
    value: number,
    t: (key: string) => string,
): {
    label: string;
    color: string;
    bgColor: string;
} {
    if (value < NORMAL_RANGE_MIN) {
        return {
            label: t('health_entries.glucose_statistics.status.low'),
            color: 'text-orange-600',
            bgColor: 'bg-orange-50',
        };
    }
    if (value > NORMAL_RANGE_MAX) {
        return {
            label: t('health_entries.glucose_statistics.status.high'),
            color: 'text-red-600',
            bgColor: 'bg-red-50',
        };
    }
    return {
        label: t('health_entries.glucose_statistics.status.normal'),
        color: 'text-green-600',
        bgColor: 'bg-green-50',
    };
}

export default function GlucoseStatistics({ readings }: Props) {
    const { t } = useTranslation('common');
    const stats = calculateStatistics(readings);
    const avgLevel = getGlucoseLevel(stats.average, t);

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('health_entries.glucose_statistics.average_title')}
                    </CardTitle>
                    <Activity className="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.average} mg/dL
                    </div>
                    <p className={`text-xs font-medium ${avgLevel.color} mt-1`}>
                        {t('health_entries.glucose_statistics.average_range', {
                            label: avgLevel.label,
                        })}
                    </p>
                    <CardDescription className="mt-1">
                        {stats.totalReadings === 1
                            ? t(
                                  'health_entries.glucose_statistics.based_on_readings',
                                  { count: stats.totalReadings },
                              )
                            : t(
                                  'health_entries.glucose_statistics.based_on_readings_plural',
                                  { count: stats.totalReadings },
                              )}
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('health_entries.glucose_statistics.highest_title')}
                    </CardTitle>
                    <ArrowUp className="size-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.highest} mg/dL
                    </div>
                    <p
                        className={`text-xs font-medium ${getGlucoseLevel(stats.highest, t).color} mt-1`}
                    >
                        {getGlucoseLevel(stats.highest, t).label}
                    </p>
                    <CardDescription className="mt-1">
                        {t(
                            'health_entries.glucose_statistics.highest_description',
                        )}
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('health_entries.glucose_statistics.lowest_title')}
                    </CardTitle>
                    <ArrowDown className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.lowest} mg/dL
                    </div>
                    <p
                        className={`text-xs font-medium ${getGlucoseLevel(stats.lowest, t).color} mt-1`}
                    >
                        {getGlucoseLevel(stats.lowest, t).label}
                    </p>
                    <CardDescription className="mt-1">
                        {t(
                            'health_entries.glucose_statistics.lowest_description',
                        )}
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t(
                            'health_entries.glucose_statistics.time_in_range_title',
                        )}
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
                            ? t(
                                  'health_entries.glucose_statistics.status.excellent',
                              )
                            : stats.timeInRange >= 50
                              ? t(
                                    'health_entries.glucose_statistics.status.good',
                                )
                              : t(
                                    'health_entries.glucose_statistics.status.needs_improvement',
                                )}
                    </p>
                    <CardDescription className="mt-1">
                        {t(
                            'health_entries.glucose_statistics.time_in_range_target',
                        )}
                    </CardDescription>
                </CardContent>
            </Card>
        </div>
    );
}
