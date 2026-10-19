import { hasFood } from '@/lib/health-entry-registry';
import { cn } from '@/lib/utils';
import type { HealthEntry } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import { CALORIES, formatAmount, MACROS, sumNutrition } from './macros';

export default function DaySummary({ entries }: { entries: HealthEntry[] }) {
    const { t, i18n } = useTranslation('common');
    const meals = entries.filter(hasFood);

    if (meals.length === 0) {
        return null;
    }

    const totals = sumNutrition(meals);
    const locale = i18n.resolvedLanguage;

    return (
        <section
            aria-labelledby="day-total-heading"
            className="flex flex-col gap-4 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60"
        >
            <div className="flex items-center justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <h2
                        id="day-total-heading"
                        className="text-sm text-muted-foreground"
                    >
                        {t('health_entries.logbook.day_total')}
                    </h2>
                    <p className="flex items-baseline gap-1.5">
                        <span className="text-3xl font-bold tracking-tight tabular-nums">
                            {formatAmount(totals.calories ?? 0, locale)}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {t('health_entries.nutrition.kcal')}
                        </span>
                    </p>
                </div>
                <span
                    aria-hidden="true"
                    className="flex size-12 items-center justify-center rounded-2xl bg-orange-100 dark:bg-orange-500/15"
                >
                    <CALORIES.Icon className={cn('size-6', CALORIES.icon)} />
                </span>
            </div>
            <dl className="grid grid-cols-3 gap-2">
                {MACROS.map((macro) => (
                    <div
                        key={macro.key}
                        className={cn(
                            'flex min-w-0 items-center gap-2 rounded-2xl p-2.5',
                            macro.card,
                        )}
                    >
                        <macro.Icon
                            className={cn('size-4 shrink-0', macro.icon)}
                            aria-hidden="true"
                        />
                        <div className="flex min-w-0 flex-col">
                            <dt className="truncate text-xs text-muted-foreground">
                                {t(macro.labelKey)}
                            </dt>
                            <dd className="text-sm font-semibold tabular-nums">
                                {t('health_entries.nutrition.grams', {
                                    value: formatAmount(
                                        totals[macro.field] ?? 0,
                                        locale,
                                    ),
                                })}
                            </dd>
                        </div>
                    </div>
                ))}
            </dl>
        </section>
    );
}
