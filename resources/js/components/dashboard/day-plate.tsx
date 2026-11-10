import { DAY_PART_STARTS, type DayPart } from '@/components/chat/day-part';
import { STARTERS, type StarterId } from '@/components/chat/starters';
import { cn } from '@/lib/utils';
import { Moon, Sun } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

export const DISPLAY_FONT = "font-['SN_Pro',Inter,ui-sans-serif,sans-serif]";

const HOURS_IN_DAY = 24;
const DAY_MS = HOURS_IN_DAY * 60 * 60 * 1000;

const MOMENTS: { id: StarterId; hour: number }[] = [
    { id: 'glucose', hour: 6.5 },
    { id: 'breakfast', hour: 8.5 },
    { id: 'lunch', hour: 12.5 },
    { id: 'workout', hour: 16.5 },
    { id: 'dinner', hour: 19 },
    { id: 'sleep', hour: 22.5 },
];

const MOMENT_OF: Record<DayPart, StarterId> = {
    morning: 'breakfast',
    midday: 'lunch',
    evening: 'dinner',
    night: 'sleep',
};

const GLAZE: Record<DayPart, string> = {
    morning: 'stroke-amber-400',
    midday: 'stroke-emerald-400',
    evening: 'stroke-orange-400',
    night: 'stroke-indigo-400',
};

const VIEW = 400;
const GLAZE_RADIUS = 189;
const GLAZE_LENGTH = 2 * Math.PI * GLAZE_RADIUS;
const GLAZE_GAP_HOURS = 0.5;
const MOMENT_ORBIT = 36.5;

const RIM = DAY_PART_STARTS.map(({ part, hour }, index) => {
    const next = DAY_PART_STARTS[index + 1]?.hour;
    const hours = (next ?? DAY_PART_STARTS[0].hour + HOURS_IN_DAY) - hour;
    const painted = ((hours - GLAZE_GAP_HOURS) / HOURS_IN_DAY) * GLAZE_LENGTH;

    return {
        part,
        dashes: `${painted} ${GLAZE_LENGTH - painted}`,
        offset: -((hour + GLAZE_GAP_HOURS / 2) / HOURS_IN_DAY) * GLAZE_LENGTH,
    };
});

function rimPoint(hour: number): { left: string; top: string } {
    const angle = (hour / HOURS_IN_DAY) * 2 * Math.PI;

    return {
        left: `${50 - MOMENT_ORBIT * Math.sin(angle)}%`,
        top: `${50 + MOMENT_ORBIT * Math.cos(angle)}%`,
    };
}

const PRESSABLE =
    'cursor-pointer touch-manipulation select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400';

export function DayPlate({
    now,
    dayPart,
    locale,
    onPick,
    className,
}: {
    now: Date;
    dayPart: DayPart;
    locale: string;
    onPick: (prompt: string) => void;
    className?: string;
}) {
    const { t } = useTranslation('common');
    const [previewId, setPreviewId] = useState<StarterId | null>(null);
    const [hasRisen, setHasRisen] = useState(false);
    const [dayStart] = useState(() => new Date(now).setHours(0, 0, 0, 0));

    useEffect(() => {
        const frame = requestAnimationFrame(() => setHasRisen(true));

        return () => cancelAnimationFrame(frame);
    }, []);

    const clock = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                hour: 'numeric',
                minute: '2-digit',
            }),
        [locale],
    );

    const turns = (now.getTime() - dayStart) / DAY_MS;
    const currentId = MOMENT_OF[dayPart];
    const shownId = previewId ?? currentId;
    const ShownIcon = STARTERS[shownId].Icon;
    const Light = dayPart === 'night' ? Moon : Sun;

    return (
        <div
            role="group"
            aria-label={t('chat.starters.label')}
            className={cn('@container relative aspect-square', className)}
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-[2%] top-[50%] -bottom-[11%] rounded-[50%] bg-radial-[closest-side] from-black/30 from-50% to-transparent dark:from-black/80"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 rounded-full bg-white shadow-[0_1px_2px_rgb(0_0_0/0.08),0_8px_16px_-6px_rgb(0_0_0/0.14)] ring-1 inset-shadow-[0_-10px_24px_rgb(0_0_0/0.035)] ring-black/[0.04] dark:bg-neutral-800 dark:shadow-none dark:inset-shadow-[0_1px_0_rgb(255_255_255/0.08)] dark:ring-white/[0.08]"
            />
            <svg
                viewBox={`0 0 ${VIEW} ${VIEW}`}
                aria-hidden="true"
                className="absolute inset-0 size-full"
            >
                {RIM.map(({ part, dashes, offset }) => (
                    <circle
                        key={part}
                        cx={VIEW / 2}
                        cy={VIEW / 2}
                        r={GLAZE_RADIUS}
                        fill="none"
                        strokeWidth={7}
                        strokeLinecap="round"
                        strokeDasharray={dashes}
                        strokeDashoffset={offset}
                        transform={`rotate(90 ${VIEW / 2} ${VIEW / 2})`}
                        className={cn(
                            'transition-opacity duration-700 motion-reduce:transition-none',
                            GLAZE[part],
                            part === dayPart ? 'opacity-100' : 'opacity-30',
                        )}
                    />
                ))}
            </svg>

            <div
                aria-hidden="true"
                style={{
                    transform: `rotate(${(hasRisen ? turns : turns - 0.25) * 360}deg)`,
                }}
                className="pointer-events-none absolute top-1/2 left-1/2 size-0 transition-transform duration-[1600ms] ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
            >
                <span className="absolute top-[47.25cqw] left-0 flex size-[max(1.25rem,6.5cqw)] -translate-1/2 items-center justify-center rounded-full bg-white shadow-[0_2px_8px_rgb(0_0_0/0.2)] ring-1 ring-black/5 dark:bg-neutral-700 dark:ring-white/10">
                    <Light
                        className={cn(
                            'size-[62%]',
                            dayPart === 'night'
                                ? 'fill-indigo-300 text-indigo-400 dark:fill-indigo-300 dark:text-indigo-300'
                                : 'fill-amber-400 text-amber-500 dark:fill-amber-300 dark:text-amber-300',
                        )}
                        strokeWidth={2.25}
                    />
                </span>
            </div>

            <button
                type="button"
                data-test="day-plate-now"
                onClick={() => onPick(t(`chat.starters.${currentId}.prompt`))}
                className={cn(
                    'absolute inset-[20%] flex flex-col items-center justify-center gap-[1.75cqw] rounded-full bg-neutral-50 px-[4cqw] text-center inset-shadow-[0_3px_10px_rgb(0_0_0/0.07)] transition-[scale,background-color] duration-200 ease-out hover:bg-neutral-100/80 active:scale-[0.97] dark:bg-neutral-900/70 dark:inset-shadow-[0_3px_12px_rgb(0_0_0/0.5)] dark:hover:bg-neutral-900',
                    PRESSABLE,
                )}
            >
                <ShownIcon
                    className="size-[8.5cqw] text-foreground/80"
                    strokeWidth={1.75}
                    aria-hidden="true"
                />
                <span
                    className={cn(
                        'text-[6.2cqw] leading-[1.12] font-bold tracking-[-0.01em] text-balance text-foreground',
                        DISPLAY_FONT,
                    )}
                >
                    {t(`chat.starters.${shownId}.label`)}
                </span>
                <time
                    dateTime={now.toISOString()}
                    className="text-[3.9cqw] leading-none font-semibold text-muted-foreground tabular-nums"
                >
                    {clock.format(now)}
                </time>
            </button>

            {MOMENTS.map(({ id, hour }) => {
                const { Icon, tint } = STARTERS[id];
                const isCurrent = id === currentId;

                return (
                    <button
                        key={id}
                        type="button"
                        data-test="day-plate-moment"
                        aria-label={t(`chat.starters.${id}.label`)}
                        style={rimPoint(hour)}
                        onClick={() => onPick(t(`chat.starters.${id}.prompt`))}
                        onPointerEnter={() => setPreviewId(id)}
                        onPointerLeave={() => setPreviewId(null)}
                        onFocus={() => setPreviewId(id)}
                        onBlur={() => setPreviewId(null)}
                        className={cn(
                            'absolute flex size-[max(2.25rem,11.5cqw)] -translate-1/2 items-center justify-center rounded-full transition-[scale,background-color] duration-150 ease-out before:absolute before:-inset-1.5 before:rounded-full hover:scale-110 active:scale-90',
                            PRESSABLE,
                            tint,
                            isCurrent &&
                                'bg-foreground text-background shadow-[0_6px_14px_-6px_rgb(0_0_0/0.5)] dark:bg-foreground dark:text-background',
                        )}
                    >
                        <Icon
                            className="size-[46%]"
                            strokeWidth={2}
                            aria-hidden="true"
                        />
                    </button>
                );
            })}
        </div>
    );
}
