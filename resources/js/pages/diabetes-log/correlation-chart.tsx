import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

interface DiabetesLogEntry {
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

interface Props {
    logs: DiabetesLogEntry[];
}

interface ChartDataPoint {
    date: string;
    dateLabel: string;
    glucose: number | null;
    insulin: number | null;
    insulinType: string | null;
    carbs: number | null;
    exercise: number | null;
    exerciseType: string | null;
    fullDate: Date;
}

const NORMAL_RANGE_MIN = 70;
const NORMAL_RANGE_MAX = 140;

function prepareChartData(logs: DiabetesLogEntry[]): ChartDataPoint[] {
    // Group logs by date
    const groupedByDate = new Map<string, DiabetesLogEntry[]>();

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
}

function CustomTooltip({ active, payload, label }: TooltipProps) {
    if (active && payload && payload.length) {
        return (
            <div className="rounded-lg border bg-background p-3 shadow-lg">
                <p className="mb-2 text-sm font-semibold">{label}</p>
                <div className="space-y-1">
                    {payload.map((entry, index) => {
                        if (entry.value === null || entry.value === 0)
                            return null;
                        const labels: Record<string, string> = {
                            glucose: 'Glucose',
                            insulin: 'Insulin',
                            carbs: 'Carbs',
                            exercise: 'Exercise',
                        };
                        const units: Record<string, string> = {
                            glucose: 'mg/dL',
                            insulin: 'units',
                            carbs: 'g',
                            exercise: 'min',
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

export default function CorrelationChart({ logs }: Props) {
    const chartData = prepareChartData(logs);

    // Check if we have any meaningful data
    const hasGlucose = chartData.some((d) => d.glucose !== null);
    const hasInsulin = chartData.some((d) => d.insulin !== null);
    const hasCarbs = chartData.some((d) => d.carbs !== null);
    const hasExercise = chartData.some((d) => d.exercise !== null);

    if (!hasGlucose && !hasInsulin && !hasCarbs && !hasExercise) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Glucose & Factor Correlation</CardTitle>
                    <CardDescription>
                        See how insulin, carbs, and exercise affect your glucose
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex h-[400px] items-center justify-center text-muted-foreground">
                        No data available. Add log entries to see correlations.
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Calculate Y-axis domains
    const glucoseValues = chartData
        .filter((d) => d.glucose !== null)
        .map((d) => d.glucose!);
    const glucoseMin =
        glucoseValues.length > 0 ? Math.min(...glucoseValues) : 0;
    const glucoseMax =
        glucoseValues.length > 0 ? Math.max(...glucoseValues) : 200;
    const yGlucoseMin = Math.max(0, glucoseMin - 20);
    const yGlucoseMax = glucoseMax + 20;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Glucose & Factor Correlation</CardTitle>
                <CardDescription>
                    Daily averages showing how insulin, carbs, and exercise
                    correlate with glucose levels
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {/* Legend */}
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        {hasGlucose && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-blue-500" />
                                <span className="text-muted-foreground">
                                    Glucose (mg/dL)
                                </span>
                            </div>
                        )}
                        {hasInsulin && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded bg-purple-500" />
                                <span className="text-muted-foreground">
                                    Insulin (units)
                                </span>
                            </div>
                        )}
                        {hasCarbs && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded bg-amber-500" />
                                <span className="text-muted-foreground">
                                    Carbs (g)
                                </span>
                            </div>
                        )}
                        {hasExercise && (
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-green-500" />
                                <span className="text-muted-foreground">
                                    Exercise (min)
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Chart */}
                    <ResponsiveContainer width="100%" height={400}>
                        <ComposedChart
                            data={chartData}
                            margin={{ top: 20, right: 30, left: 0, bottom: 5 }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                className="stroke-muted"
                            />
                            <XAxis
                                dataKey="dateLabel"
                                className="text-xs"
                                tick={{ fill: 'hsl(var(--muted-foreground))' }}
                            />
                            <YAxis
                                yAxisId="glucose"
                                domain={[yGlucoseMin, yGlucoseMax]}
                                className="text-xs"
                                tick={{ fill: 'hsl(var(--muted-foreground))' }}
                                label={{
                                    value: 'Glucose (mg/dL)',
                                    angle: -90,
                                    position: 'insideLeft',
                                    style: {
                                        fill: 'hsl(var(--muted-foreground))',
                                        fontSize: '12px',
                                    },
                                }}
                            />
                            <YAxis
                                yAxisId="other"
                                orientation="right"
                                className="text-xs"
                                tick={{ fill: 'hsl(var(--muted-foreground))' }}
                                label={{
                                    value: 'Units / Grams / Min',
                                    angle: 90,
                                    position: 'insideRight',
                                    style: {
                                        fill: 'hsl(var(--muted-foreground))',
                                        fontSize: '12px',
                                    },
                                }}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend />

                            {/* Reference lines for glucose range */}
                            <ReferenceLine
                                yAxisId="glucose"
                                y={NORMAL_RANGE_MAX}
                                stroke="#ef4444"
                                strokeDasharray="3 3"
                                strokeOpacity={0.5}
                            />
                            <ReferenceLine
                                yAxisId="glucose"
                                y={NORMAL_RANGE_MIN}
                                stroke="#f97316"
                                strokeDasharray="3 3"
                                strokeOpacity={0.5}
                            />

                            {/* Glucose line */}
                            {hasGlucose && (
                                <Line
                                    yAxisId="glucose"
                                    type="monotone"
                                    dataKey="glucose"
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

                            {/* Insulin bars */}
                            {hasInsulin && (
                                <Bar
                                    yAxisId="other"
                                    dataKey="insulin"
                                    fill="#a855f7"
                                    opacity={0.7}
                                    name="Insulin"
                                />
                            )}

                            {/* Carbs bars */}
                            {hasCarbs && (
                                <Bar
                                    yAxisId="other"
                                    dataKey="carbs"
                                    fill="#f59e0b"
                                    opacity={0.5}
                                    name="Carbs"
                                />
                            )}

                            {/* Exercise scatter points */}
                            {hasExercise && (
                                <Scatter
                                    yAxisId="other"
                                    dataKey="exercise"
                                    fill="#22c55e"
                                    name="Exercise"
                                />
                            )}
                        </ComposedChart>
                    </ResponsiveContainer>

                    {/* Insights */}
                    <div className="border-t pt-4">
                        <p className="mb-2 text-sm font-medium">
                            Correlation Insights
                        </p>
                        <div className="space-y-2 text-xs text-muted-foreground">
                            {hasInsulin && hasGlucose && (
                                <p>
                                    💉 <strong>Insulin:</strong> Track how your
                                    insulin doses affect glucose levels over
                                    time.
                                </p>
                            )}
                            {hasCarbs && hasGlucose && (
                                <p>
                                    🍞 <strong>Carbs:</strong> Notice patterns
                                    between carb intake and glucose spikes.
                                </p>
                            )}
                            {hasExercise && hasGlucose && (
                                <p>
                                    🏃 <strong>Exercise:</strong> See how
                                    physical activity impacts your glucose
                                    control.
                                </p>
                            )}
                            {!hasInsulin && !hasCarbs && !hasExercise && (
                                <p>
                                    💡 Log insulin, carbs, or exercise to see
                                    how they correlate with your glucose levels.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
