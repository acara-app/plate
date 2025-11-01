import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
}

interface ChartDataPoint {
    date: string;
    time: string;
    value: number;
    type: string;
    fullDate: Date;
}

const NORMAL_RANGE_MIN = 70;
const NORMAL_RANGE_MAX = 140;

function prepareChartData(readings: GlucoseReading[]): ChartDataPoint[] {
    return readings
        .map((reading) => {
            const date = new Date(reading.measured_at);
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
                type: reading.reading_type,
                fullDate: date,
            };
        })
        .sort((a, b) => a.fullDate.getTime() - b.fullDate.getTime());
}

interface TooltipProps {
    active?: boolean;
    payload?: Array<{ payload: ChartDataPoint }>;
}

function CustomTooltip({ active, payload }: TooltipProps) {
    if (active && payload && payload.length) {
        const data = payload[0].payload;
        const value = data.value;
        let status = 'Normal';
        let statusColor = 'text-green-600';

        if (value < NORMAL_RANGE_MIN) {
            status = 'Low';
            statusColor = 'text-orange-600';
        } else if (value > NORMAL_RANGE_MAX) {
            status = 'High';
            statusColor = 'text-red-600';
        }

        return (
            <div className="rounded-lg border bg-background p-3 shadow-lg">
                <p className="text-sm font-semibold">{data.date}</p>
                <p className="text-xs text-muted-foreground">{data.time}</p>
                <div className="mt-2 flex items-baseline gap-2">
                    <span className="text-2xl font-bold">{value}</span>
                    <span className="text-sm text-muted-foreground">mg/dL</span>
                </div>
                <p className={`text-xs font-medium ${statusColor} mt-1`}>
                    {status}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {data.type}
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

function getDataPointColor(value: number): string {
    if (value < NORMAL_RANGE_MIN) return '#f97316'; // orange
    if (value > NORMAL_RANGE_MAX) return '#ef4444'; // red
    return '#10b981'; // green
}

export default function GlucoseChart({ readings }: Props) {
    const chartData = prepareChartData(readings);

    if (readings.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Glucose Trends</CardTitle>
                    <CardDescription>
                        Track your glucose levels over time
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex h-[400px] items-center justify-center text-muted-foreground">
                        No data available. Add glucose readings to see trends.
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Calculate Y-axis domain with padding
    const values = chartData.map((d) => d.value);
    const minValue = Math.min(...values);
    const maxValue = Math.max(...values);
    const padding = 20;
    const yMin = Math.max(0, minValue - padding);
    const yMax = maxValue + padding;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Glucose Trends</CardTitle>
                <CardDescription>
                    Track your glucose levels over time with target range zones
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {/* Legend */}
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        <div className="flex items-center gap-2">
                            <div className="size-3 rounded-full bg-red-500" />
                            <span className="text-muted-foreground">
                                High (&gt;140 mg/dL)
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="size-3 rounded-full bg-green-500" />
                            <span className="text-muted-foreground">
                                Normal (70-140 mg/dL)
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="size-3 rounded-full bg-orange-500" />
                            <span className="text-muted-foreground">
                                Low (&lt;70 mg/dL)
                            </span>
                        </div>
                    </div>

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
                                    value: 'mg/dL',
                                    angle: -90,
                                    position: 'insideLeft',
                                    style: {
                                        fill: 'hsl(var(--muted-foreground))',
                                        fontSize: '12px',
                                    },
                                }}
                            />
                            <Tooltip content={<CustomTooltip />} />

                            {/* Reference lines for normal range */}
                            <ReferenceLine
                                y={NORMAL_RANGE_MAX}
                                stroke="#ef4444"
                                strokeDasharray="3 3"
                                strokeOpacity={0.5}
                                label={{
                                    value: 'High',
                                    position: 'right',
                                    fill: '#ef4444',
                                    fontSize: 11,
                                }}
                            />
                            <ReferenceLine
                                y={NORMAL_RANGE_MIN}
                                stroke="#f97316"
                                strokeDasharray="3 3"
                                strokeOpacity={0.5}
                                label={{
                                    value: 'Low',
                                    position: 'right',
                                    fill: '#f97316',
                                    fontSize: 11,
                                }}
                            />

                            {/* Main glucose line */}
                            <Line
                                type="monotone"
                                dataKey="value"
                                stroke="hsl(var(--primary))"
                                strokeWidth={2}
                                dot={(props: DotProps) => {
                                    const { cx, cy, payload } = props;
                                    if (
                                        cx === undefined ||
                                        cy === undefined ||
                                        !payload
                                    )
                                        return <></>;
                                    const color = getDataPointColor(
                                        payload.value,
                                    );
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
                            Reading Types
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
