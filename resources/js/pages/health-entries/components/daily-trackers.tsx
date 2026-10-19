import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import type { DailyWellness } from '@/types/diabetes';
import { GlassWater, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { formatAmount } from './macros';
import WaterSheet from './water-sheet';
import { WATER_TONE } from './wellness';

const CARD =
    'flex min-w-0 flex-col gap-4 rounded-3xl bg-card p-4 text-left shadow-xs ring-1 ring-border/60';

export default function DailyTrackers({
    date,
    isToday,
    wellness,
}: {
    date: string;
    isToday: boolean;
    wellness: DailyWellness;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const [sheet, setSheet] = useState({ key: 0, open: false });
    const water = formatAmount(wellness.water_ml, locale);
    const waterGoal = formatAmount(wellness.water_goal_ml, locale);

    return (
        <div className="grid grid-cols-2 gap-3">
            <section
                aria-labelledby="water-heading"
                className="flex min-w-0 flex-col gap-3"
            >
                <h2
                    id="water-heading"
                    className="text-xl font-semibold tracking-tight"
                >
                    {t('health_entries.trackers.water_heading')}
                </h2>
                <button
                    type="button"
                    onClick={() =>
                        setSheet((current) => ({
                            key: current.key + 1,
                            open: true,
                        }))
                    }
                    aria-label={t('health_entries.trackers.water_label', {
                        value: water,
                        goal: waterGoal,
                    })}
                    className={cn(
                        CARD,
                        'transition-colors outline-none hover:bg-accent/60 focus-visible:ring-[3px] focus-visible:ring-primary/50',
                    )}
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <GlassWater
                            className={cn('size-6 shrink-0', WATER_TONE.icon)}
                            aria-hidden="true"
                        />
                        <span className="truncate text-sm text-muted-foreground">
                            {isToday
                                ? t('health_entries.trackers.water_today')
                                : t('health_entries.trackers.water_logged')}
                        </span>
                    </span>
                    <span
                        className="flex w-full flex-col gap-3"
                        aria-hidden="true"
                    >
                        <span className="flex flex-wrap items-baseline gap-x-0.5">
                            <span className="text-3xl font-bold tracking-tight tabular-nums">
                                {water}
                            </span>
                            <span className="text-sm text-muted-foreground tabular-nums">
                                {t('health_entries.logbook.goal_of', {
                                    value: waterGoal,
                                    unit: '',
                                })}
                            </span>
                        </span>
                        <Progress
                            value={
                                wellness.water_goal_ml > 0
                                    ? (wellness.water_ml /
                                          wellness.water_goal_ml) *
                                      100
                                    : 0
                            }
                            className={cn('h-2', WATER_TONE.track)}
                            indicatorClassName={WATER_TONE.indicator}
                        />
                    </span>
                </button>
            </section>
            <div className="flex min-w-0 flex-col gap-3">
                <div aria-hidden="true" className="h-7" />
                <div aria-disabled="true" className={CARD}>
                    <Sparkles
                        className="size-6 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <span className="flex flex-col gap-3">
                        <span className="flex min-h-9 items-end text-lg font-semibold text-muted-foreground">
                            {t('health_entries.trackers.coming_soon')}
                        </span>
                        <span
                            aria-hidden="true"
                            className="h-2 w-full rounded-full bg-muted"
                        />
                    </span>
                </div>
            </div>
            <WaterSheet
                key={`${date}:${sheet.key}`}
                open={sheet.open}
                onClose={() =>
                    setSheet((current) => ({ ...current, open: false }))
                }
                date={date}
                wellness={wellness}
            />
        </div>
    );
}
