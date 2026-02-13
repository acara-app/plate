import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { convertGlucoseValue } from '@/lib/utils';
import {
    type DiabetesTrackingPageProps,
    GlucoseUnit,
    type GlucoseUnitType,
    type HealthEntry,
} from '@/types/diabetes';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    Bar,
    CartesianGrid,
    ComposedChart,
    Legend,
    Line,
    ReferenceLine,
    ResponsiveContainer,
    Scatter,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface ChartDataPoint {
    date: string;
    dateLabel: string;
    glucose: number | null;
    glucoseDisplay: number | null; // Converted for display
    insulin: number | null;
    insulinType: string | null;
    carbs: number | null;
    exercise: number | null;
    exerciseType: string | null;
    fullDate: Date;
}

const NORMAL_RANGE_MIN = 70;
const NORMAL_RANGE_MAX = 140;

function prepareChartData(
    logs: HealthEntry[],
    glucoseUnit: GlucoseUnitType,
): ChartDataPoint[] {
    // Group logs by date
    const groupedByDate = new Map<string, HealthEntry[]>();

    logs.forEach((log) => {
        const date = new Date(log.measured_at);
        const dateKey = date.toISOString().split('T')[0];
        if (!groupedByDate.has(dateKey)) {
            groupedByDate.set(dateKey, []);
        }
        groupedByDate.get(dateKey)!.push(log);
    });

    // Create data points for each date
    const dataPoints: ChartDataPoint[] = [];

    groupedByDate.forEach((dayLogs, dateKey) => {
        const date = new Date(dateKey);

        // Calculate averages/totals for the day
        const glucoseValues = dayLogs
            .filter((l) => l.glucose_value !== null)
            .map((l) => l.glucose_value!);
        const avgGlucose =
            glucoseValues.length > 0
                ? Math.round(
                      glucoseValues.reduce((a, b) => a + b, 0) /
                          glucoseValues.length,
                  )
                : null;

        const insulinTotal = dayLogs.reduce(
            (sum, l) => sum + (l.insulin_units || 0),
            0,
        );
        const insulinTypes = [
            ...new Set(
                dayLogs
                    .filter((l) => l.insulin_type)
                    .map((l) => l.insulin_type),
            ),
        ];

        const carbsTotal = dayLogs.reduce(
            (sum, l) => sum + (l.carbs_grams || 0),
            0,
        );

        const exerciseTotal = dayLogs.reduce(
            (sum, l) => sum + (l.exercise_duration_minutes || 0),
            0,
        );
        const exerciseTypes = [
            ...new Set(
                dayLogs
                    .filter((l) => l.exercise_type)
                    .map((l) => l.exercise_type),
            ),
        ];

        dataPoints.push({
            date: dateKey,
            dateLabel: date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            }),
            glucose: avgGlucose,
            glucoseDisplay:
                avgGlucose !== null
                    ? convertGlucoseValue(avgGlucose, glucoseUnit)
                    : null,
            insulin: insulinTotal > 0 ? insulinTotal : null,
            insulinType: insulinTypes.join(', ') || null,
            carbs: carbsTotal > 0 ? carbsTotal : null,
            exercise: exerciseTotal > 0 ? exerciseTotal : null,
            exerciseType: exerciseTypes.join(', ') || null,
            fullDate: date,
        });
    });

    return dataPoints.sort(
        (a, b) => a.fullDate.getTime() - b.fullDate.getTime(),
    );
}

interface TooltipProps {
    active?: boolean;
    payload?: Array<{ dataKey: string; value: number; color: string }>;
    label?: string;
    glucoseUnit: GlucoseUnitType;
}

function CustomTooltip({ active, payload, label, glucoseUnit }: TooltipProps) {
    const { t } = useTranslation('common');
    if (active && payload && payload.length) {
        return (
            <div className="rounded-lg border bg-background p-3 shadow-lg">
                <p className="mb-2 text-sm font-semibold">{label}</p>
                <div className="space-y-1">
                    {payload.map((entry, index) => {
                        if (entry.value === null || entry.value === 0) {
                            return null;
                        }
                        const labels: Record<string, string> = {
                            glucoseDisplay: t(
                                'health_entries.correlation_chart.tooltip.glucose',
                            ),
                            insulin: t(
                                'health_entries.correlation_chart.tooltip.insulin',
                            ),
                            carbs: t(
                                'health_entries.correlation_chart.tooltip.carbs',
                            ),
                            exercise: t(
                                'health_entries.correlation_chart.tooltip.exercise',
                            ),
                        };
                        const units: Record<string, string> = {
                            glucoseDisplay: glucoseUnit,
                            insulin: t(
                                'health_entries.correlation_chart.tooltip.units',
                            ),
                            carbs: t(
                                'health_entries.correlation_chart.tooltip.grams',
                            ),
                            exercise: t(
                                'health_entries.correlation_chart.tooltip.minutes',
                            ),
                        };
                        return (
                            <div
                                key={index}
                                className="flex items-center gap-2 text-sm"
                            >
                                <div
                                    className="size-3 rounded-full"
                                    style={{ backgroundColor: entry.color }}
                                />
                                <span className="text-muted-foreground">
                                    {labels[entry.dataKey] || entry.dataKey}:
                                </span>
                                <span className="font-medium">
                                    {entry.value} {units[entry.dataKey] || ''}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    }
    return null;
}

export default function CorrelationChart() {
    const { t } = useTranslation('common');
    const { logs, glucoseUnit, summary } =
        usePage<DiabetesTrackingPageProps>().props;

    const { dataTypes } = summary;
    const chartData = prepareChartData(logs, glucoseUnit);

    // Check if we have any meaningful data
    const hasGlucose = chartData.some((d) => d.glucose !== null);
    const hasInsulin = chartData.some((d) => d.insulin !== null);
    const hasCarbs = chartData.some((d) => d.carbs !== null);
    const hasExercise = chartData.some((d) => d.exercise !== null);

    if (!hasGlucose && !hasInsulin && !hasCarbs && !hasExercise) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>
                        {t('health_entries.correlation_chart.title')}
                    </CardTitle>
                    <CardDescription>
                        {t('health_entries.correlation_chart.description')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex h-[400px] items-center justify-center text-muted-foreground">
                        {t('health_entries.correlation_chart.no_data')}
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Calculate Y-axis domains using display values
    const glucoseDisplayValues = chartData
        .filter((d) => d.glucoseDisplay !== null)
        .map((d) => d.glucoseDisplay!);
    const glucoseMin =
        glucoseDisplayValues.length > 0 ? Math.min(...glucoseDisplayValues) : 0;
    const glucoseMax =
        glucoseDisplayValues.length > 0
            ? Math.max(...glucoseDisplayValues)
            : glucoseUnit === GlucoseUnit.MmolL
              ? 11
              : 200;
    const padding = glucoseUnit === GlucoseUnit.MmolL ? 1 : 20;
    const yGlucoseMin = Math.max(0, glucoseMin - padding);
    const yGlucoseMax = glucoseMax + padding;

    // Convert reference lines to display unit
    const normalRangeMinDisplay = convertGlucoseValue(
        NORMAL_RANGE_MIN,
        glucoseUnit,
    );
    const normalRangeMaxDisplay = convertGlucoseValue(
        NORMAL_RANGE_MAX,
        glucoseUnit,
    );

    const insulinValues = chartData
        .filter((d) => d.insulin !== null)
        .map((d) => d.insulin!);
    const carbValues = chartData
        .filter((d) => d.carbs !== null)
        .map((d) => d.carbs!);
    const exerciseValues = chartData
        .filter((d) => d.exercise !== null)
        .map((d) => d.exercise!);

    const allSecondaryValues = [
        ...insulinValues,
        ...carbValues,
        ...exerciseValues,
    ];
    const secondaryMax =
        allSecondaryValues.length > 0 ? Math.max(...allSecondaryValues) : 100;
    // Add 20% padding to max value
    const ySecondaryMax = Math.ceil(secondaryMax * 1.2);

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {t('health_entries.correlation_chart.title')}
                </CardTitle>
                <CardDescription>
                    {t('health_entries.correlation_chart.description')}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {/* Glucose Chart - Top */}
                    <div>
                        <p className="mb-2 text-sm font-medium text-muted-foreground">
                            {t(
                                'health_entries.correlation_chart.glucose_levels',
                                { unit: glucoseUnit },
                            )}
                        </p>
                        <ResponsiveContainer width="100%" height={200}>
                            <ComposedChart
                                data={chartData}
                                margin={{
                                    top: 10,
                                    right: 10,
                                    left: 0,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    className="stroke-muted"
                                />
                                <XAxis dataKey="dateLabel" hide />
                                <YAxis
                                    domain={[yGlucoseMin, yGlucoseMax]}
                                    className="text-xs"
                                    tick={{
                                        fill: 'hsl(var(--muted-foreground))',
                                    }}
                                    width={50}
                                />
                                <Tooltip
                                    content={
                                        <CustomTooltip
                                            glucoseUnit={glucoseUnit}
                                        />
                                    }
                                />

                                {/* Reference lines for glucose range */}
                                <ReferenceLine
                                    y={normalRangeMaxDisplay}
                                    stroke="#ef4444"
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.5}
                                />
                                <ReferenceLine
                                    y={normalRangeMinDisplay}
                                    stroke="#f97316"
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.5}
                                />

                                {/* Glucose line - using display values */}
                                {hasGlucose && (
                                    <Line
                                        type="monotone"
                                        dataKey="glucoseDisplay"
                                        stroke="#3b82f6"
                                        strokeWidth={2}
                                        dot={{
                                            fill: '#3b82f6',
                                            stroke: 'white',
                                            strokeWidth: 2,
                                            r: 4,
                                        }}
                                        name="Glucose"
                                        connectNulls
                                    />
                                )}
                            </ComposedChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Insulin/Carbs/Exercise Chart - Bottom */}
                    {(hasInsulin || hasCarbs || hasExercise) && (
                        <div>
                            <p className="mb-2 text-sm font-medium text-muted-foreground">
                                {t('health_entries.correlation_chart.factors')}
                            </p>
                            <ResponsiveContainer width="100%" height={180}>
                                <ComposedChart
                                    data={chartData}
                                    margin={{
                                        top: 10,
                                        right: 10,
                                        left: 0,
                                        bottom: 5,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-muted"
                                    />
                                    <XAxis
                                        dataKey="dateLabel"
                                        className="text-xs"
                                        tick={{
                                            fill: 'hsl(var(--muted-foreground))',
                                        }}
                                    />
                                    <YAxis
                                        domain={[0, ySecondaryMax]}
                                        className="text-xs"
                                        tick={{
                                            fill: 'hsl(var(--muted-foreground))',
                                        }}
                                        width={50}
                                    />
                                    <Tooltip
                                        content={
                                            <CustomTooltip
                                                glucoseUnit={glucoseUnit}
                                            />
                                        }
                                    />
                                    <Legend />

                                    {/* Insulin bars */}
                                    {hasInsulin && (
                                        <Bar
                                            dataKey="insulin"
                                            fill="#a855f7"
                                            opacity={0.8}
                                            name="Insulin"
                                            barSize={20}
                                        />
                                    )}

                                    {/* Carbs bars */}
                                    {hasCarbs && (
                                        <Bar
                                            dataKey="carbs"
                                            fill="#f59e0b"
                                            opacity={0.6}
                                            name="Carbs"
                                            barSize={20}
                                        />
                                    )}

                                    {/* Exercise scatter points */}
                                    {hasExercise && (
                                        <Scatter
                                            dataKey="exercise"
                                            fill="#22c55e"
                                            name="Exercise"
                                        />
                                    )}
                                </ComposedChart>
                            </ResponsiveContainer>
                        </div>
                    )}

                    {/* Legend */}
                    <div className="flex flex-wrap items-center gap-4 border-t pt-4 text-sm">
                        {hasGlucose && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-blue-500" />
                                <span className="text-muted-foreground">
                                    {t(
                                        'health_entries.correlation_chart.legend.glucose',
                                        { unit: glucoseUnit },
                                    )}
                                </span>
                            </div>
                        )}
                        {hasInsulin && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded bg-purple-500" />
                                <span className="text-muted-foreground">
                                    {t(
                                        'health_entries.correlation_chart.legend.insulin',
                                    )}
                                </span>
                            </div>
                        )}
                        {hasCarbs && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded bg-amber-500" />
                                <span className="text-muted-foreground">
                                    {t(
                                        'health_entries.correlation_chart.legend.carbs',
                                    )}
                                </span>
                            </div>
                        )}
                        {hasExercise && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-green-500" />
                                <span className="text-muted-foreground">
                                    {t(
                                        'health_entries.correlation_chart.legend.exercise',
                                    )}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Insights */}
                    <div className="border-t pt-4">
                        <p className="mb-2 text-sm font-medium">
                            {t(
                                'health_entries.correlation_chart.insights.title',
                            )}
                        </p>
                        <div className="space-y-2 text-xs text-muted-foreground">
                            {dataTypes.hasInsulin && dataTypes.hasGlucose && (
                                <p
                                    dangerouslySetInnerHTML={{
                                        __html: t(
                                            'health_entries.correlation_chart.insights.insulin',
                                        ),
                                    }}
                                />
                            )}
                            {dataTypes.hasCarbs && dataTypes.hasGlucose && (
                                <p
                                    dangerouslySetInnerHTML={{
                                        __html: t(
                                            'health_entries.correlation_chart.insights.carbs',
                                        ),
                                    }}
                                />
                            )}
                            {dataTypes.hasExercise && dataTypes.hasGlucose && (
                                <p
                                    dangerouslySetInnerHTML={{
                                        __html: t(
                                            'health_entries.correlation_chart.insights.exercise',
                                        ),
                                    }}
                                />
                            )}
                            {!dataTypes.hasInsulin &&
                                !dataTypes.hasCarbs &&
                                !dataTypes.hasExercise && (
                                    <p
                                        dangerouslySetInnerHTML={{
                                            __html: t(
                                                'health_entries.correlation_chart.insights.no_factors',
                                            ),
                                        }}
                                    />
                                )}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
