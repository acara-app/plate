import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ShowHealthEntryController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { getMealTypeConfig, mealTypeForTime } from '@/lib/meal-types';
import { cn } from '@/lib/utils';
import {
    formatAmount,
    MACROS,
    sumNutrition,
} from '@/pages/health-entries/components/macros';
import type { DailyNutritionGoals, HealthEntry } from '@/types/diabetes';
import type { MealType } from '@/types/meal-plan';
import { Link } from '@inertiajs/react';
import { ScanLine } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export const DISPLAY_FONT = "font-['SN_Pro',Inter,ui-sans-serif,sans-serif]";

const VIEW = 400;
const RADIUS = 172;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
const STROKE = 18;
const GAP = 10;
const ORBIT = (RADIUS / VIEW) * 100;
const SWEEP = 300 / 360;
const OPENING_START = 210 / 360;
const ARC = SWEEP * CIRCUMFERENCE;
const PIN_ROOM = 54;
const STAGGER_MS = 90;
const MAX_STAGGER = 6;

const GLAZE: Record<MealType, { stroke: string; tile: string }> = {
    breakfast: {
        stroke: 'stroke-amber-400',
        tile: 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-300',
    },
    lunch: {
        stroke: 'stroke-emerald-500 dark:stroke-emerald-400',
        tile: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300',
    },
    dinner: {
        stroke: 'stroke-indigo-400',
        tile: 'bg-indigo-100 text-indigo-500 dark:bg-indigo-950 dark:text-indigo-300',
    },
    snack: {
        stroke: 'stroke-rose-400',
        tile: 'bg-rose-100 text-rose-500 dark:bg-rose-950 dark:text-rose-300',
    },
};

const PLATE_MACROS = MACROS.filter((macro) => macro.key !== 'fiber');

const PRESSABLE =
    'cursor-pointer touch-manipulation select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400';

type Meal = HealthEntry & { meal_type: MealType; calories: number };

function isMeal(entry: HealthEntry): entry is Meal {
    return entry.meal_type !== null && (entry.calories ?? 0) > 0;
}

function stagger(index: number, offset = 0): { animationDelay: string } {
    return {
        animationDelay: `${Math.min(index, MAX_STAGGER) * STAGGER_MS + offset}ms`,
    };
}

function MealPin({ meal }: { meal: Meal }) {
    const [photoFailed, setPhotoFailed] = useState(false);
    const { Icon } = getMealTypeConfig(meal.meal_type);

    if (meal.photo_url && !photoFailed) {
        return (
            <img
                src={meal.photo_url}
                alt=""
                onError={() => setPhotoFailed(true)}
                className="size-full object-cover"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex size-full items-center justify-center',
                GLAZE[meal.meal_type].tile,
            )}
        >
            <Icon className="size-[48%]" strokeWidth={2} />
        </span>
    );
}

export function IntakePlate({
    entries,
    goals,
    now,
    className,
}: {
    entries: HealthEntry[];
    goals: DailyNutritionGoals | null;
    now: Date;
    className?: string;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const totals = sumNutrition(entries);
    const eaten = totals.calories ?? 0;
    const scale = Math.max(goals?.calories ?? 0, eaten, 1);
    const left = goals ? goals.calories - eaten : null;
    const isOver = left !== null && left < 0;
    const meals = entries
        .filter(isMeal)
        .sort((a, b) => a.measured_at.localeCompare(b.measured_at));

    let cursor = 0;
    const segments = meals.map((meal) => {
        const start = cursor / scale;
        const share = meal.calories / scale;
        const angle =
            (OPENING_START + (start + share / 2) * SWEEP) * 2 * Math.PI;
        cursor += meal.calories;

        return {
            meal,
            hasPin: share * ARC >= PIN_ROOM,
            dashes: `${Math.max(share * ARC - GAP - STROKE, 0.01)} ${CIRCUMFERENCE}`,
            offset: -(start * ARC + (GAP + STROKE) / 2),
            left: `${50 + ORBIT * Math.sin(angle)}%`,
            top: `${50 - ORBIT * Math.cos(angle)}%`,
        };
    });

    return (
        <div
            data-test="intake-plate"
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
                className="absolute inset-0 size-full rotate-[120deg]"
            >
                <circle
                    cx={VIEW / 2}
                    cy={VIEW / 2}
                    r={RADIUS}
                    fill="none"
                    strokeWidth={STROKE}
                    strokeLinecap="round"
                    strokeDasharray={`${ARC - STROKE} ${CIRCUMFERENCE}`}
                    strokeDashoffset={-STROKE / 2}
                    className="stroke-neutral-100 dark:stroke-neutral-700/60"
                />
                {segments.map(({ meal, dashes, offset }, index) => (
                    <circle
                        key={meal.id}
                        cx={VIEW / 2}
                        cy={VIEW / 2}
                        r={RADIUS}
                        fill="none"
                        strokeWidth={STROKE}
                        strokeLinecap="round"
                        strokeDasharray={dashes}
                        strokeDashoffset={offset}
                        style={stagger(index)}
                        className={cn(
                            'duration-300 ease-out fill-mode-both motion-safe:animate-in motion-safe:fade-in',
                            GLAZE[meal.meal_type].stroke,
                        )}
                    />
                ))}
            </svg>

            <Link
                href={ListHealthEntryController().url}
                prefetch
                data-test="intake-plate-today"
                className={cn(
                    'absolute inset-[22%] flex flex-col items-center justify-center rounded-full bg-neutral-50 text-center inset-shadow-[0_3px_10px_rgb(0_0_0/0.07)] transition-[scale,background-color] duration-200 ease-out hover:bg-neutral-100/80 active:scale-[0.97] dark:bg-neutral-900/70 dark:inset-shadow-[0_3px_12px_rgb(0_0_0/0.5)] dark:hover:bg-neutral-900',
                    PRESSABLE,
                )}
            >
                <span className="text-[max(0.75rem,3.8cqw)] font-medium text-muted-foreground">
                    {t('dashboard_ai.plate.today')}
                </span>
                <span
                    className={cn(
                        'mt-[0.5cqw] text-[13cqw] leading-none font-extrabold tracking-[-0.03em] text-foreground tabular-nums',
                        DISPLAY_FONT,
                    )}
                >
                    {formatAmount(Math.abs(left ?? eaten), locale)}
                </span>
                <span
                    className={cn(
                        'mt-[1cqw] text-[max(0.75rem,3.8cqw)] font-semibold',
                        isOver
                            ? 'text-orange-600 dark:text-orange-400'
                            : 'text-muted-foreground',
                    )}
                >
                    {left === null
                        ? t('dashboard_ai.plate.kcal_today')
                        : isOver
                          ? t('dashboard_ai.plate.kcal_over')
                          : t('dashboard_ai.plate.kcal_left')}
                </span>
                <span className="mt-[3cqw] flex items-center gap-[2.5cqw] text-[max(0.6875rem,3.3cqw)] font-semibold text-foreground/80 tabular-nums">
                    {PLATE_MACROS.map((macro) => (
                        <span
                            key={macro.key}
                            className="flex items-center gap-[0.8cqw]"
                        >
                            <macro.Icon
                                className={cn(
                                    'size-[max(0.75rem,3.6cqw)]',
                                    macro.icon,
                                )}
                                strokeWidth={2.25}
                                aria-hidden="true"
                            />
                            <span className="sr-only">{t(macro.labelKey)}</span>
                            {t('health_entries.nutrition.grams', {
                                value: formatAmount(
                                    totals[macro.field] ?? 0,
                                    locale,
                                ),
                            })}
                        </span>
                    ))}
                </span>
            </Link>

            {segments.map(
                ({ meal, hasPin, left: x, top: y }, index) =>
                    hasPin && (
                        <span
                            key={meal.id}
                            style={{ left: x, top: y }}
                            className="absolute -translate-1/2"
                        >
                            <Link
                                href={ShowHealthEntryController(meal.id).url}
                                prefetch
                                data-test="intake-plate-meal"
                                aria-label={
                                    meal.meal_name ??
                                    t(
                                        getMealTypeConfig(meal.meal_type)
                                            .labelKey,
                                    )
                                }
                                style={stagger(index, 120)}
                                className={cn(
                                    'flex size-[max(2.25rem,12.5cqw)] overflow-hidden rounded-full bg-white shadow-[0_4px_10px_-4px_rgb(0_0_0/0.35)] ring-[3px] ring-white transition-[scale] duration-150 ease-out fill-mode-both hover:scale-110 active:scale-95 motion-safe:duration-300 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-75 dark:bg-neutral-800 dark:ring-neutral-800',
                                    PRESSABLE,
                                )}
                            >
                                <MealPin meal={meal} />
                            </Link>
                        </span>
                    ),
            )}

            <Link
                href={ShowSnapToTrackController().url}
                prefetch
                data-test="dashboard-snap-to-track"
                className={cn(
                    'absolute top-full left-1/2 flex h-[max(3rem,13cqw)] -translate-1/2 items-center gap-[2cqw] rounded-full bg-foreground pr-[5.5cqw] pl-[4cqw] text-[max(0.95rem,4.4cqw)] font-bold whitespace-nowrap text-background shadow-[0_12px_24px_-10px_rgb(0_0_0/0.55)] transition-[scale,opacity] duration-150 ease-out hover:opacity-90 active:scale-[0.97]',
                    DISPLAY_FONT,
                    PRESSABLE,
                )}
            >
                <ScanLine
                    className="size-[max(1.25rem,5.5cqw)] text-emerald-400 dark:text-emerald-600"
                    strokeWidth={2}
                    aria-hidden="true"
                />
                {t(`dashboard_ai.plate.snap.${mealTypeForTime(now)}`)}
            </Link>
        </div>
    );
}
