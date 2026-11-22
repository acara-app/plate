import { cn } from '@/lib/utils';
import { useId } from 'react';
import { useTranslation } from 'react-i18next';
import type { ViewProps } from './props';
import { tint } from './style';
import { useVisualFormat } from './use-visual-format';

type ChartProps = ViewProps<'BarChart'>['props'];

interface Point {
    label: string;
    value: number | null;
}

interface Scale {
    ticks: number[];
    percent: (value: number) => number;
}

const MISSING_MARK =
    'rounded-[3px] border border-dashed border-muted-foreground/70';

function seriesOf(props: ChartProps): Point[] {
    return props.data.map((row) => {
        const label = row[props.xKey];
        const value = row[props.yKey];

        return {
            label: label === null || label === undefined ? '' : String(label),
            value: typeof value === 'number' ? value : null,
        };
    });
}

function niceStep(raw: number): number {
    const power = 10 ** Math.floor(Math.log10(raw));
    const fraction = raw / power;

    return (
        (fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10) * power
    );
}

function scaleOf(values: number[], fromZero: boolean): Scale {
    let low = fromZero ? Math.min(0, ...values) : Math.min(...values);
    let high = Math.max(...values);

    if (high === low) {
        high = low + (Math.abs(low) || 1);
    }

    const step = niceStep((high - low) / 3);
    low = Math.floor(low / step) * step;
    high = Math.ceil(high / step) * step;

    const ticks: number[] = [];

    for (let tick = low; tick <= high + step / 2; tick += step) {
        ticks.push(Number(tick.toPrecision(12)));
    }

    return {
        ticks,
        percent: (value) => ((value - low) / (high - low)) * 100,
    };
}

function labelIndexes(count: number): number[] {
    if (count <= 7) {
        return Array.from({ length: count }, (_, index) => index);
    }

    return [
        ...new Set(
            Array.from({ length: 5 }, (_, index) =>
                Math.round((index * (count - 1)) / 4),
            ),
        ),
    ];
}

function barGap(count: number): string {
    if (count <= 14) {
        return 'gap-1.5';
    }

    if (count <= 31) {
        return 'gap-1';
    }

    return count <= 92 ? 'gap-px' : 'gap-0';
}

function Chart({ props, kind }: { props: ChartProps; kind: 'bar' | 'line' }) {
    const { t } = useTranslation('common');
    const format = useVisualFormat();
    const titleId = useId();
    const accent = tint(props.tint ?? 'brand');
    const series = seriesOf(props);
    const readings = series.flatMap((point) =>
        point.value === null ? [] : [point.value],
    );
    const range =
        props.range && props.range.low <= props.range.high ? props.range : null;
    const goal = typeof props.goal === 'number' ? props.goal : null;
    const unit = format.unit(props.unit);
    const quantity = (value: number): string =>
        unit ? `${format.number(value)} ${unit}` : format.number(value);
    const missing = format.label('missing_reading');
    const hasMissing = readings.length < series.length;
    const scale = scaleOf(
        [
            ...readings,
            ...(goal === null ? [] : [goal]),
            ...(range ? [range.low, range.high] : []),
        ],
        kind === 'bar',
    );
    const position = (index: number): number =>
        kind === 'bar'
            ? ((index + 0.5) / series.length) * 100
            : series.length === 1
              ? 50
              : (index / (series.length - 1)) * 100;
    const widestTick = scale.ticks
        .map((tick) => format.number(tick))
        .reduce(
            (widest, tick) => (tick.length > widest.length ? tick : widest),
            '',
        );

    return (
        <div className="flex min-w-0 flex-col gap-3">
            <div className="flex flex-wrap items-baseline justify-between gap-x-3">
                <p
                    id={titleId}
                    className="text-[15px] leading-6 font-medium text-foreground"
                >
                    {props.title}
                </p>
                {unit && (
                    <p className="text-[13px] leading-5 text-muted-foreground">
                        {unit}
                    </p>
                )}
            </div>
            {readings.length === 0 ? (
                <p className="text-sm leading-6 text-muted-foreground">
                    {missing}
                </p>
            ) : (
                <div
                    aria-hidden="true"
                    data-test="chat-chart-plot"
                    className="grid grid-cols-[minmax(0,1fr)_auto] gap-x-2 gap-y-1.5"
                >
                    <div
                        className={cn(
                            'relative h-36 @min-[28rem]:h-44',
                            accent.text,
                        )}
                    >
                        {scale.ticks.map((tick) => (
                            <div
                                key={tick}
                                className="absolute inset-x-0 border-t border-foreground/10"
                                style={{ bottom: `${scale.percent(tick)}%` }}
                            />
                        ))}
                        {range && (
                            <div
                                data-test="chat-chart-range"
                                className="absolute inset-x-0 bg-current/15"
                                style={{
                                    bottom: `${scale.percent(range.low)}%`,
                                    height: `${scale.percent(range.high) - scale.percent(range.low)}%`,
                                }}
                            />
                        )}
                        {kind === 'bar' ? (
                            <Bars
                                series={series}
                                scale={scale}
                                gap={barGap(series.length)}
                            />
                        ) : (
                            <Line
                                series={series}
                                scale={scale}
                                position={position}
                            />
                        )}
                        {goal !== null && (
                            <div
                                data-test="chat-chart-goal"
                                className="absolute inset-x-0 border-t-2 border-dashed border-foreground/40"
                                style={{ bottom: `${scale.percent(goal)}%` }}
                            />
                        )}
                    </div>
                    <div className="relative text-[11px] leading-none text-muted-foreground tabular-nums">
                        <span className="invisible">{widestTick}</span>
                        {scale.ticks.map((tick) => (
                            <span
                                key={tick}
                                className="absolute right-0 translate-y-1/2"
                                style={{ bottom: `${scale.percent(tick)}%` }}
                            >
                                {format.number(tick)}
                            </span>
                        ))}
                    </div>
                    <div className="relative h-4 text-[11px] leading-4 text-muted-foreground tabular-nums">
                        {labelIndexes(series.length).map((index) => (
                            <span
                                key={index}
                                className={cn(
                                    'absolute top-0 whitespace-nowrap',
                                    position(index) < 4
                                        ? 'translate-x-0'
                                        : position(index) > 96
                                          ? '-translate-x-full'
                                          : '-translate-x-1/2',
                                )}
                                style={{ left: `${position(index)}%` }}
                            >
                                {format.axis(series[index].label)}
                            </span>
                        ))}
                    </div>
                </div>
            )}
            {(goal !== null || range || hasMissing) && (
                <div className="flex flex-wrap gap-x-4 gap-y-1 text-[13px] leading-5 text-muted-foreground">
                    {goal !== null && (
                        <span className="flex items-center gap-1.5">
                            <span
                                aria-hidden="true"
                                className="w-4 border-t-2 border-dashed border-foreground/40"
                            />
                            {t('chat.visual.goal', { value: quantity(goal) })}
                        </span>
                    )}
                    {range && (
                        <span className="flex items-center gap-1.5">
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'h-2.5 w-4 rounded-sm bg-current/15',
                                    accent.text,
                                )}
                            />
                            {t('chat.visual.target_range', {
                                low: format.number(range.low),
                                high: quantity(range.high),
                            })}
                        </span>
                    )}
                    {hasMissing && (
                        <span className="flex items-center gap-1.5">
                            <span
                                aria-hidden="true"
                                className={cn('h-2.5 w-2', MISSING_MARK)}
                            />
                            {missing}
                        </span>
                    )}
                </div>
            )}
            {props.dayBoundary && (
                <p className="text-[13px] leading-5 text-muted-foreground">
                    {props.dayBoundary}
                </p>
            )}
            <ul aria-labelledby={titleId} className="sr-only">
                {series.map((point, index) => (
                    <li key={index}>
                        {t('chat.visual.point', {
                            label: format.pointLabel(point.label),
                            value:
                                point.value === null
                                    ? missing
                                    : quantity(point.value),
                        })}
                    </li>
                ))}
            </ul>
        </div>
    );
}

function Bars({
    series,
    scale,
    gap,
}: {
    series: Point[];
    scale: Scale;
    gap: string;
}) {
    return (
        <div className={cn('absolute inset-0 flex items-end', gap)}>
            {series.map((point, index) => (
                <div
                    key={index}
                    data-test="chat-chart-bar"
                    data-reading={
                        point.value === null
                            ? 'missing'
                            : point.value === 0
                              ? 'zero'
                              : 'value'
                    }
                    className="flex h-full min-w-0 flex-1 flex-col justify-end"
                >
                    {point.value === null ? (
                        <div className={cn('h-2', MISSING_MARK)} />
                    ) : point.value === 0 ? (
                        <div className="h-0.5 rounded-full bg-current" />
                    ) : (
                        <div
                            className="rounded-t-[4px] bg-current"
                            style={{
                                height: `max(4px, ${scale.percent(point.value) - scale.percent(0)}%)`,
                            }}
                        />
                    )}
                </div>
            ))}
        </div>
    );
}

function Line({
    series,
    scale,
    position,
}: {
    series: Point[];
    scale: Scale;
    position: (index: number) => number;
}) {
    const segments: string[][] = [[]];

    series.forEach((point, index) => {
        if (point.value === null) {
            segments.push([]);

            return;
        }

        segments[segments.length - 1].push(
            `${position(index)},${100 - scale.percent(point.value)}`,
        );
    });

    const dotted = series.length <= 31;

    return (
        <>
            <svg
                viewBox="0 0 100 100"
                preserveAspectRatio="none"
                className="absolute inset-0 size-full overflow-visible"
            >
                {segments
                    .filter((points) => points.length > 1)
                    .map((points) => (
                        <polyline
                            key={points[0]}
                            points={points.join(' ')}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={2}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            vectorEffect="non-scaling-stroke"
                        />
                    ))}
            </svg>
            {series.map((point, index) => {
                const isolated =
                    point.value !== null &&
                    (series[index - 1]?.value ?? null) === null &&
                    (series[index + 1]?.value ?? null) === null;

                if (point.value === null) {
                    return (
                        <span
                            key={index}
                            data-test="chat-chart-point"
                            data-reading="missing"
                            className="absolute bottom-0 h-2 -translate-x-1/2 border-l border-dashed border-muted-foreground/70"
                            style={{ left: `${position(index)}%` }}
                        />
                    );
                }

                return (
                    <span
                        key={index}
                        data-test="chat-chart-point"
                        data-reading={point.value === 0 ? 'zero' : 'value'}
                        className={cn(
                            'absolute size-2 -translate-x-1/2 translate-y-1/2 rounded-full bg-current',
                            !dotted && !isolated && 'hidden',
                        )}
                        style={{
                            left: `${position(index)}%`,
                            bottom: `${scale.percent(point.value)}%`,
                        }}
                    />
                );
            })}
        </>
    );
}

export function BarChart({ props }: ViewProps<'BarChart'>) {
    return <Chart props={props} kind="bar" />;
}

export function LineChart({ props }: ViewProps<'LineChart'>) {
    return <Chart props={props} kind="line" />;
}
