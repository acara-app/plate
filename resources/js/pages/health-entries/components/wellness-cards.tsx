import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import type { DailyWellness } from '@/types/diabetes';
import { Footprints, Lightbulb, Moon, Plus } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import CheckInSheet from './check-in-sheet';
import { formatAmount } from './macros';
import {
    ACTIVITY_TONE,
    SLEEP_QUALITY_OPTIONS,
    SLEEP_TONE,
    STRESS_OPTIONS,
    type CheckInStep,
} from './wellness';

const CARD =
    'flex min-h-24 min-w-0 flex-col justify-between gap-3 rounded-3xl bg-card p-4 text-left shadow-xs ring-1 ring-border/60';

export default function WellnessCards({
    date,
    wellness,
}: {
    date: string;
    wellness: DailyWellness;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const [checkIn, setCheckIn] = useState<{
        key: number;
        open: boolean;
        startStep: CheckInStep;
    }>({ key: 0, open: false, startStep: 'hours' });

    const openCheckIn = (startStep: CheckInStep) =>
        setCheckIn((current) => ({
            key: current.key + 1,
            open: true,
            startStep,
        }));
    const sleepQuality = SLEEP_QUALITY_OPTIONS.find(
        (option) => option.value === wellness.sleep_quality,
    );
    const stress = STRESS_OPTIONS.find(
        (option) => option.value === wellness.stress_level,
    );
    const sleepLabel = t('health_entries.wellness.sleep');
    const stressLabel = t('health_entries.wellness.stress');
    const steps = formatAmount(wellness.steps, locale);
    const stepGoal = formatAmount(wellness.step_goal, locale);

    return (
        <section
            aria-label={t('health_entries.wellness.heading')}
            className="grid grid-cols-3 gap-3"
        >
            <CheckInCard
                label={
                    wellness.sleep_hours === null
                        ? t('health_entries.wellness.log_sleep')
                        : sleepLabel
                }
                onClick={() => openCheckIn('hours')}
                icon={
                    <Moon
                        className={cn(
                            'size-6',
                            wellness.sleep_hours === null
                                ? 'text-muted-foreground'
                                : SLEEP_TONE.icon,
                        )}
                        aria-hidden="true"
                    />
                }
                value={
                    wellness.sleep_hours === null
                        ? null
                        : t('health_entries.wellness.hours_short', {
                              value: formatAmount(wellness.sleep_hours, locale),
                          })
                }
                caption={sleepQuality ? t(sleepQuality.labelKey) : sleepLabel}
                emptyLabel={sleepLabel}
            />
            <div
                role="group"
                aria-label={t('health_entries.wellness.steps_label', {
                    steps,
                    goal: stepGoal,
                })}
                className={CARD}
            >
                <Footprints
                    className={cn('size-6', ACTIVITY_TONE.icon)}
                    aria-hidden="true"
                />
                <div className="flex flex-col gap-2" aria-hidden="true">
                    <p className="flex flex-wrap items-baseline gap-x-0.5">
                        <span className="text-lg font-bold tracking-tight tabular-nums">
                            {steps}
                        </span>
                        <span className="text-sm text-muted-foreground tabular-nums">
                            {t('health_entries.wellness.steps_goal', {
                                value: formatAmount(
                                    wellness.step_goal / 1000,
                                    locale,
                                ),
                            })}
                        </span>
                    </p>
                    <Progress
                        value={
                            wellness.step_goal > 0
                                ? (wellness.steps / wellness.step_goal) * 100
                                : 0
                        }
                        className="h-1.5"
                        indicatorClassName={ACTIVITY_TONE.indicator}
                    />
                </div>
            </div>
            <CheckInCard
                label={
                    stress
                        ? stressLabel
                        : t('health_entries.wellness.log_stress')
                }
                onClick={() => openCheckIn('stress')}
                icon={
                    stress ? (
                        <stress.Icon
                            className={cn('size-6', stress.icon)}
                            aria-hidden="true"
                        />
                    ) : (
                        <Lightbulb
                            className="size-6 text-muted-foreground"
                            aria-hidden="true"
                        />
                    )
                }
                value={stress ? t(stress.labelKey) : null}
                caption={stressLabel}
                emptyLabel={stressLabel}
            />
            <CheckInSheet
                key={`${date}:${wellness.sleep_synced}:${checkIn.key}`}
                open={checkIn.open}
                onClose={() =>
                    setCheckIn((current) => ({ ...current, open: false }))
                }
                date={date}
                wellness={wellness}
                startStep={checkIn.startStep}
            />
        </section>
    );
}

function CheckInCard({
    label,
    onClick,
    icon,
    value,
    caption,
    emptyLabel,
}: {
    label: string;
    onClick: () => void;
    icon: ReactNode;
    value: string | null;
    caption: string;
    emptyLabel: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={value === null ? label : `${label}: ${value}`}
            className={cn(
                CARD,
                'transition-colors outline-none hover:bg-accent/60 focus-visible:ring-[3px] focus-visible:ring-primary/50',
            )}
        >
            <span className="flex w-full items-center justify-between gap-2">
                {icon}
                {value === null && (
                    <Plus
                        className="size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                )}
            </span>
            {value === null ? (
                <span className="truncate text-sm text-muted-foreground">
                    {emptyLabel}
                </span>
            ) : (
                <span className="flex min-w-0 flex-col">
                    <span className="truncate text-lg font-bold tracking-tight tabular-nums">
                        {value}
                    </span>
                    <span className="truncate text-xs text-muted-foreground">
                        {caption}
                    </span>
                </span>
            )}
        </button>
    );
}
