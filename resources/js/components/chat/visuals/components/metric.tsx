import { cn } from '@/lib/utils';
import { ArrowDownRight, ArrowRight, ArrowUpRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { ViewProps } from './props';
import { tint, visualIcon } from './style';
import { useVisualFormat } from './use-visual-format';

const TRENDS = {
    up: ArrowUpRight,
    down: ArrowDownRight,
    neutral: ArrowRight,
} as const;

function Sparkline({
    data,
    className,
}: {
    data: (number | null)[];
    className: string;
}) {
    const readings = data.filter((value): value is number => value !== null);

    if (readings.length < 2) {
        return null;
    }

    const low = Math.min(...readings);
    const span = Math.max(...readings) - low || 1;
    const segments: string[][] = [[]];

    data.forEach((value, index) => {
        if (value === null) {
            segments.push([]);

            return;
        }

        const x = (index / (data.length - 1)) * 100;
        const y = 30 - ((value - low) / span) * 28;

        segments[segments.length - 1].push(`${x},${y}`);
    });

    return (
        <svg
            viewBox="0 0 100 32"
            preserveAspectRatio="none"
            className={cn('mt-1 h-8 w-full overflow-visible', className)}
            aria-hidden="true"
        >
            {segments
                .filter((points) => points.length > 0)
                .map((points) => (
                    <polyline
                        key={points[0]}
                        points={
                            points.length === 1
                                ? `${points[0]} ${points[0]}`
                                : points.join(' ')
                        }
                        fill="none"
                        stroke="currentColor"
                        strokeWidth={2}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        vectorEffect="non-scaling-stroke"
                    />
                ))}
        </svg>
    );
}

export function Metric({ props }: ViewProps<'Metric'>) {
    const { t } = useTranslation('visuals');
    const format = useVisualFormat();
    const Icon = visualIcon(props.icon);
    const accent = tint(props.tint ?? props.icon);
    const Trend = props.trend ? TRENDS[props.trend] : null;

    return (
        <div className="flex min-w-0 flex-col gap-0.5">
            <p className="flex items-center gap-1.5 text-[13px] leading-5 text-muted-foreground">
                {Icon && (
                    <Icon
                        className={cn('size-3.5 shrink-0', accent.text)}
                        aria-hidden="true"
                    />
                )}
                {props.label}
            </p>
            <p className="flex flex-wrap items-baseline gap-x-1.5">
                <span className="text-2xl font-semibold tracking-tight text-foreground tabular-nums">
                    {format.quantity(props.value)}
                </span>
                {props.unit && (
                    <span className="text-sm text-muted-foreground">
                        {format.unit(props.unit)}
                    </span>
                )}
                {Trend && props.trend && (
                    <span className="self-center text-muted-foreground">
                        <Trend className="size-4" aria-hidden="true" />
                        <span className="sr-only">
                            {t(`trend.${props.trend}`)}
                        </span>
                    </span>
                )}
            </p>
            {props.detail && (
                <p className="text-[13px] leading-5 text-muted-foreground">
                    {props.detail}
                </p>
            )}
            {props.data && (
                <Sparkline data={props.data} className={accent.text} />
            )}
        </div>
    );
}

export function Progress({ props }: ViewProps<'Progress'>) {
    const { t } = useTranslation('common');
    const format = useVisualFormat();
    const share = Math.min(Math.max(props.value / props.goal, 0), 1);
    const unit = format.unit(props.unit);
    const summary = t('chat.visual.progress', {
        value: format.number(props.value),
        goal: unit
            ? `${format.number(props.goal)} ${unit}`
            : format.number(props.goal),
    });
    const meter = {
        role: 'meter',
        'aria-label': props.label,
        'aria-valuemin': 0,
        'aria-valuemax': props.goal,
        'aria-valuenow': Math.min(Math.max(props.value, 0), props.goal),
        'aria-valuetext': summary,
    } as const;

    if (props.style === 'ring') {
        return (
            <div {...meter} className="flex items-center gap-4">
                <svg
                    viewBox="0 0 36 36"
                    className="size-16 shrink-0 -rotate-90"
                    aria-hidden="true"
                >
                    <circle
                        cx="18"
                        cy="18"
                        r="15"
                        fill="none"
                        strokeWidth="4"
                        className="stroke-foreground/10"
                    />
                    <circle
                        cx="18"
                        cy="18"
                        r="15"
                        fill="none"
                        strokeWidth="4"
                        strokeLinecap="round"
                        pathLength={100}
                        strokeDasharray={`${share * 100} 100`}
                        className={cn(
                            'stroke-emerald-600 dark:stroke-emerald-400',
                            share === 0 && 'hidden',
                        )}
                    />
                </svg>
                <div className="min-w-0">
                    <p className="text-[13px] leading-5 text-muted-foreground">
                        {props.label}
                    </p>
                    <p className="text-xl font-semibold tracking-tight text-foreground tabular-nums">
                        {format.number(
                            Math.round((props.value / props.goal) * 100),
                        )}
                        %
                    </p>
                    <p className="text-[13px] leading-5 text-muted-foreground tabular-nums">
                        {summary}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div {...meter} className="flex flex-col gap-2">
            <div className="flex flex-wrap items-baseline justify-between gap-x-3 text-[13px] leading-5">
                <span className="font-medium text-foreground/80">
                    {props.label}
                </span>
                <span className="text-muted-foreground tabular-nums">
                    {summary}
                </span>
            </div>
            <div
                className="h-2 overflow-hidden rounded-full bg-foreground/10"
                aria-hidden="true"
            >
                <div
                    className="h-full rounded-full bg-emerald-600 dark:bg-emerald-400"
                    style={{ width: `${share * 100}%` }}
                />
            </div>
        </div>
    );
}
