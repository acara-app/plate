import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { convertGlucoseValue } from '@/lib/utils';
import {
    GlucoseThresholds,
    GlucoseUnit,
    type GlucoseUnitType,
} from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import {
    CartesianGrid,
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

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
    glucoseUnit: GlucoseUnitType;
}

interface ChartDataPoint {
    date: string;
    time: string;
    value: number;
    displayValue: number;
    type: string;
    fullDate: Date;
    isFasting: boolean;
    movingAvg: number | null;
}

// Context-aware thresholds based on reading type (in mg/dL - converted as needed)
const FASTING_THRESHOLDS_MGDL = {
    low: 70,
    normalMax: 100,
    high: 140,
};

const POSTMEAL_THRESHOLDS_MGDL = {
    low: 70,
    normalMax: 180,
    high: 200,
};

function calculateMovingAverage(
    values: number[],
    index: number,
    window: number = 3,
): number | null {
    const start = Math.max(0, index - Math.floor(window / 2));
    const end = Math.min(values.length, index + Math.ceil(window / 2));
    const windowValues = values.slice(start, end);

    if (windowValues.length < 2) {
        return null;
    }

    return (
        Math.round(
            (windowValues.reduce((a, b) => a + b, 0) / windowValues.length) *
                10,
        ) / 10
    );
}

function prepareChartData(
    readings: GlucoseReading[],
    glucoseUnit: GlucoseUnitType,
): ChartDataPoint[] {
    const sortedReadings = [...readings].sort(
        (a, b) =>
            new Date(a.measured_at).getTime() -
            new Date(b.measured_at).getTime(),
    );

    const rawValues = sortedReadings.map((r) => r.reading_value);

    return sortedReadings.map((reading, index) => {
        const date = new Date(reading.measured_at);
        const fastingTypes = ['fasting', 'before-meal', 'before meal'];
        const isFasting = fastingTypes.some((ft) =>
            reading.reading_type.toLowerCase().includes(ft),
        );

        const movingAvgRaw = calculateMovingAverage(rawValues, index, 5);

        return {
            date: date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            }),
            time: date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
            }),
            value: reading.reading_value,
            displayValue: convertGlucoseValue(
                reading.reading_value,
                glucoseUnit,
            ),
            type: reading.reading_type,
            fullDate: date,
            isFasting,
            movingAvg: movingAvgRaw
                ? convertGlucoseValue(movingAvgRaw, glucoseUnit)
                : null,
        };
    });
}

interface TooltipProps {
    active?: boolean;
    payload?: Array<{ payload: ChartDataPoint }>;
    glucoseUnit: GlucoseUnitType;
}

function CustomTooltip({ active, payload, glucoseUnit }: TooltipProps) {
    const { t } = useTranslation('common');
    if (active && payload && payload.length) {
        const data = payload[0].payload;
        const value = data.value;
        const displayValue = data.displayValue;
        const thresholds = data.isFasting
            ? FASTING_THRESHOLDS_MGDL
            : POSTMEAL_THRESHOLDS_MGDL;

        let status = t('diabetes_log.glucose_chart.status.normal');
        let statusColor = 'text-green-600';
        let contextNote = '';

        if (value < thresholds.low) {
            status = t('diabetes_log.glucose_chart.status.low');
            statusColor = 'text-orange-600';
        } else if (value > thresholds.high) {
            status = t('diabetes_log.glucose_chart.status.high');
            statusColor = 'text-red-600';
        } else if (value > thresholds.normalMax) {
            status = t('diabetes_log.glucose_chart.status.elevated');
            statusColor = 'text-yellow-600';
        }

        // Context-aware note with localized values from the centralized thresholds
        const thresholdConfig = data.isFasting
            ? GlucoseThresholds.fasting[glucoseUnit]
            : GlucoseThresholds.postMeal[glucoseUnit];

        if (data.isFasting) {
            contextNote = t('diabetes_log.glucose_chart.targets.fasting', {
                target: thresholdConfig.normal,
                unit: glucoseUnit,
            });
        } else {
            contextNote = t('diabetes_log.glucose_chart.targets.post_meal', {
                target: thresholdConfig.normal,
                unit: glucoseUnit,
            });
        }

        return (
            <div className="rounded-lg border bg-background p-3 shadow-lg">
                <p className="text-sm font-semibold">{data.date}</p>
                <p className="text-xs text-muted-foreground">{data.time}</p>
                <div className="mt-2 flex items-baseline gap-2">
                    <span className="text-2xl font-bold">{displayValue}</span>
                    <span className="text-sm text-muted-foreground">
                        {glucoseUnit}
                    </span>
                </div>
                <p className={`text-xs font-medium ${statusColor} mt-1`}>
                    {status}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {data.type}
                </p>
                <p className="mt-2 text-xs text-muted-foreground italic">
                    {contextNote}
                </p>
            </div>
        );
    }
    return null;
}

interface DotProps {
    cx?: number;
    cy?: number;
    payload?: ChartDataPoint;
}

function getDataPointColor(value: number, isFasting: boolean): string {
    const thresholds = isFasting
        ? FASTING_THRESHOLDS_MGDL
        : POSTMEAL_THRESHOLDS_MGDL;

    if (value < thresholds.low) {
        return '#f97316';
    }

    if (value > thresholds.high) {
        return '#ef4444';
    }
    if (value > thresholds.normalMax) {
        return '#eab308';
    }
    return '#10b981';
}

export default function GlucoseChart({ readings, glucoseUnit }: Props) {
    const { t } = useTranslation('common');
    const chartData = prepareChartData(readings, glucoseUnit);

    // Get thresholds for the current unit
    const fastingThresholds = GlucoseThresholds.fasting[glucoseUnit];
    const postMealThresholds = GlucoseThresholds.postMeal[glucoseUnit];

    // Convert thresholds for display
    const lowThreshold = convertGlucoseValue(70, glucoseUnit);
    const fastingTarget = convertGlucoseValue(100, glucoseUnit);
    const postMealTarget = convertGlucoseValue(180, glucoseUnit);

    if (readings.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>
                        {t('diabetes_log.glucose_chart.title')}
                    </CardTitle>
                    <CardDescription>
                        {t('diabetes_log.glucose_chart.description')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex h-[400px] items-center justify-center text-muted-foreground">
                        {t('diabetes_log.glucose_chart.no_data')}
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Calculate Y-axis domain with padding (using display values)
    const displayValues = chartData.map((d) => d.displayValue);
    const minValue = Math.min(...displayValues);
    const maxValue = Math.max(...displayValues);
    const padding = glucoseUnit === GlucoseUnit.MmolL ? 1 : 20;
    const yMin = Math.max(0, minValue - padding);
    const yMax = maxValue + padding;

    // Check if we have mixed reading types
    const hasFasting = chartData.some((d) => d.isFasting);
    const hasPostMeal = chartData.some((d) => !d.isFasting);
    const hasMixedTypes = hasFasting && hasPostMeal;

    // Check if data is sparse (average gap > 2 days)
    const hasMovingAvg = chartData.some((d) => d.movingAvg !== null);
    const isSparse =
        chartData.length >= 3 &&
        chartData.length > 0 &&
        (() => {
            const firstDate = chartData[0].fullDate.getTime();
            const lastDate = chartData[chartData.length - 1].fullDate.getTime();
            const daySpan = (lastDate - firstDate) / (1000 * 60 * 60 * 24);
            return daySpan / chartData.length > 2;
        })();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('diabetes_log.glucose_chart.title')}</CardTitle>
                <CardDescription>
                    {t('diabetes_log.glucose_chart.description_with_unit', {
                        unit: glucoseUnit,
                    })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {/* Context-aware Legend */}
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        {hasFasting && (
                            <>
                                <div className="flex items-center gap-2">
                                    <div className="size-3 rotate-45 bg-green-500" />
                                    <span className="text-muted-foreground">
                                        {t(
                                            'diabetes_log.glucose_chart.legend.fasting_normal',
                                            { range: fastingThresholds.normal },
                                        )}
                                    </span>
                                </div>
                            </>
                        )}
                        {hasPostMeal && (
                            <>
                                <div className="flex items-center gap-2">
                                    <div className="size-3 rounded-full bg-green-500" />
                                    <span className="text-muted-foreground">
                                        {t(
                                            'diabetes_log.glucose_chart.legend.post_meal_normal',
                                            {
                                                range: postMealThresholds.normal,
                                            },
                                        )}
                                    </span>
                                </div>
                            </>
                        )}
                        <div className="flex items-center gap-2">
                            <div className="size-3 rounded-full bg-orange-500" />
                            <span className="text-muted-foreground">
                                {t('diabetes_log.glucose_chart.legend.low', {
                                    threshold: fastingThresholds.low,
                                })}
                            </span>
                        </div>
                        {isSparse && hasMovingAvg && (
                            <div className="flex items-center gap-2">
                                <div className="h-0.5 w-4 bg-purple-400" />
                                <span className="text-muted-foreground">
                                    {t(
                                        'diabetes_log.glucose_chart.legend.trend',
                                    )}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Info banner for mixed types */}
                    {hasMixedTypes && (
                        <div
                            className="rounded-md bg-blue-50 p-3 text-xs text-blue-800 dark:bg-blue-950 dark:text-blue-200"
                            dangerouslySetInnerHTML={{
                                __html: t(
                                    'diabetes_log.glucose_chart.context_banner',
                                ),
                            }}
                        />
                    )}

                    {/* Chart */}
                    <ResponsiveContainer width="100%" height={400}>
                        <LineChart
                            data={chartData}
                            margin={{ top: 5, right: 20, left: 0, bottom: 5 }}
                        >
                            <defs>
                                <linearGradient
                                    id="colorGradient"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stopColor="#10b981"
                                        stopOpacity={0.3}
                                    />
                                    <stop
                                        offset="100%"
                                        stopColor="#10b981"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                className="stroke-muted"
                            />
                            <XAxis
                                dataKey="date"
                                className="text-xs"
                                tick={{ fill: 'hsl(var(--muted-foreground))' }}
                            />
                            <YAxis
                                domain={[yMin, yMax]}
                                className="text-xs"
                                tick={{ fill: 'hsl(var(--muted-foreground))' }}
                                label={{
                                    value: glucoseUnit,
                                    angle: -90,
                                    position: 'insideLeft',
                                    style: {
                                        fill: 'hsl(var(--muted-foreground))',
                                        fontSize: '12px',
                                    },
                                }}
                            />
                            <Tooltip
                                content={
                                    <CustomTooltip glucoseUnit={glucoseUnit} />
                                }
                            />

                            {/* Reference lines with localized values */}
                            {hasPostMeal && (
                                <ReferenceLine
                                    y={postMealTarget}
                                    stroke="#eab308"
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.5}
                                    label={{
                                        value: t(
                                            'diabetes_log.glucose_chart.reference_labels.post_meal',
                                        ),
                                        position: 'right',
                                        fill: '#eab308',
                                        fontSize: 10,
                                    }}
                                />
                            )}
                            {hasFasting && (
                                <ReferenceLine
                                    y={fastingTarget}
                                    stroke="#10b981"
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.5}
                                    label={{
                                        value: t(
                                            'diabetes_log.glucose_chart.reference_labels.fasting',
                                        ),
                                        position: 'right',
                                        fill: '#10b981',
                                        fontSize: 10,
                                    }}
                                />
                            )}
                            <ReferenceLine
                                y={lowThreshold}
                                stroke="#f97316"
                                strokeDasharray="3 3"
                                strokeOpacity={0.5}
                                label={{
                                    value: t(
                                        'diabetes_log.glucose_chart.reference_labels.low',
                                    ),
                                    position: 'right',
                                    fill: '#f97316',
                                    fontSize: 10,
                                }}
                            />

                            {/* Moving average trend line for sparse data */}
                            {isSparse && hasMovingAvg && (
                                <Line
                                    type="monotone"
                                    dataKey="movingAvg"
                                    stroke="#a855f7"
                                    strokeWidth={2}
                                    strokeDasharray="5 5"
                                    dot={false}
                                    name="Trend"
                                    connectNulls
                                />
                            )}

                            {/* Main glucose line */}
                            <Line
                                type="monotone"
                                dataKey="displayValue"
                                stroke="hsl(var(--primary))"
                                strokeWidth={2}
                                dot={(props: DotProps) => {
                                    const { cx, cy, payload } = props;
                                    if (
                                        cx === undefined ||
                                        cy === undefined ||
                                        !payload
                                    ) {
                                        return <></>;
                                    }
                                    const color = getDataPointColor(
                                        payload.value,
                                        payload.isFasting,
                                    );
                                    if (payload.isFasting) {
                                        return (
                                            <polygon
                                                points={`${cx},${cy - 5} ${cx + 5},${cy} ${cx},${cy + 5} ${cx - 5},${cy}`}
                                                fill={color}
                                                stroke="white"
                                                strokeWidth={2}
                                            />
                                        );
                                    }
                                    return (
                                        <circle
                                            cx={cx}
                                            cy={cy}
                                            r={4}
                                            fill={color}
                                            stroke="white"
                                            strokeWidth={2}
                                        />
                                    );
                                }}
                                activeDot={{
                                    r: 6,
                                    fill: 'hsl(var(--primary))',
                                    stroke: 'white',
                                    strokeWidth: 2,
                                }}
                            />
                        </LineChart>
                    </ResponsiveContainer>

                    {/* Reading type indicators */}
                    <div className="border-t pt-4">
                        <p className="mb-2 text-sm font-medium">
                            {t(
                                'diabetes_log.glucose_chart.reading_types_title',
                            )}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {Array.from(
                                new Set(chartData.map((d) => d.type)),
                            ).map((type) => (
                                <span
                                    key={type}
                                    className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                                >
                                    {type}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
