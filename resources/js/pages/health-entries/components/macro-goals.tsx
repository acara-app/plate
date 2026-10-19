import { ProgressRing } from '@/components/ui/progress-ring';
import { cn } from '@/lib/utils';
import type { DailyNutritionGoals } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import { formatAmount, MACROS, type Nutrition } from './macros';

export default function MacroGoals({
    totals,
    goals,
}: {
    totals: Nutrition;
    goals: DailyNutritionGoals | null;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;

    return (
        <section
            aria-labelledby="macro-goals-heading"
            className="flex flex-col gap-3"
        >
            <h2
                id="macro-goals-heading"
                className="text-xl font-semibold tracking-tight"
            >
                {t('health_entries.logbook.macros_heading')}
            </h2>
            <ul className="grid grid-cols-2 gap-3">
                {MACROS.map((macro) => {
                    const eaten = totals[macro.field] ?? 0;
                    const goal = goals?.[macro.key] ?? null;
                    const percent =
                        goal !== null && goal > 0
                            ? Math.round((eaten / goal) * 100)
                            : null;
                    const icon = (
                        <macro.Icon
                            className={cn('size-5', macro.icon)}
                            aria-hidden="true"
                        />
                    );

                    return (
                        <li
                            key={macro.key}
                            className="flex min-w-0 flex-col gap-4 rounded-3xl bg-card p-4 shadow-xs ring-1 ring-border/60"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <span className="truncate pt-1 text-sm text-muted-foreground">
                                    {t(macro.labelKey)}
                                </span>
                                {percent !== null ? (
                                    <ProgressRing
                                        value={eaten / (goal ?? 1)}
                                        size={52}
                                        strokeWidth={5}
                                        indicatorClassName={macro.ring}
                                    >
                                        {icon}
                                    </ProgressRing>
                                ) : (
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'flex size-11 shrink-0 items-center justify-center rounded-full',
                                            macro.tile,
                                        )}
                                    >
                                        {icon}
                                    </span>
                                )}
                            </div>
                            <div className="flex items-end justify-between gap-2">
                                <p className="flex min-w-0 flex-wrap items-baseline gap-x-0.5">
                                    <span className="text-3xl font-bold tracking-tight tabular-nums">
                                        {formatAmount(eaten, locale)}
                                    </span>
                                    <span className="text-sm text-muted-foreground tabular-nums">
                                        {goal !== null
                                            ? t(
                                                  'health_entries.logbook.goal_of',
                                                  {
                                                      value: formatAmount(
                                                          goal,
                                                          locale,
                                                      ),
                                                      unit: t(
                                                          'health_entries.nutrition.grams_unit',
                                                      ),
                                                  },
                                              )
                                            : t(
                                                  'health_entries.nutrition.grams_unit',
                                              )}
                                    </span>
                                </p>
                                {percent !== null && (
                                    <span
                                        className={cn(
                                            'shrink-0 text-sm font-semibold tabular-nums',
                                            macro.icon,
                                        )}
                                    >
                                        {percent}%
                                    </span>
                                )}
                            </div>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
