import type { DailyExerciseSummary } from '@/types/diabetes';
import { Dumbbell } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { formatAmount } from './macros';

export default function ExerciseSummaryCard({
    summary,
}: {
    summary: DailyExerciseSummary;
}) {
    const { t, i18n } = useTranslation('common');
    const title = t('health_entries.logbook.synced_exercise');

    return (
        <div
            role="group"
            aria-label={title}
            className="flex min-h-28 overflow-hidden rounded-3xl bg-card shadow-xs ring-1 ring-border/60"
        >
            <span
                aria-hidden="true"
                className="flex w-28 shrink-0 items-center justify-center bg-muted sm:w-32"
            >
                <Dumbbell className="size-8 text-primary" />
            </span>
            <div className="flex min-w-0 flex-1 flex-col justify-center gap-1.5 p-4">
                <h3 className="leading-snug font-semibold">{title}</h3>
                <p className="text-sm font-semibold text-primary tabular-nums">
                    {t('health_entries.badges.exercise_minutes', {
                        value: formatAmount(
                            summary.total_minutes,
                            i18n.resolvedLanguage,
                        ),
                    })}
                </p>
                <p className="text-sm text-muted-foreground">
                    {t('health_entries.logbook.synced_daily_total')}
                </p>
            </div>
        </div>
    );
}
