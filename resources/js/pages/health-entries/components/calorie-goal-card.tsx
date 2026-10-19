import { ProgressRing } from '@/components/ui/progress-ring';
import { cn } from '@/lib/utils';
import biometrics from '@/routes/onboarding/biometrics';
import type { DailyNutritionGoals } from '@/types/diabetes';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CALORIES, formatAmount } from './macros';

export default function CalorieGoalCard({
    calories,
    goals,
}: {
    calories: number;
    goals: DailyNutritionGoals | null;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;

    return (
        <section
            aria-labelledby="calorie-goal-heading"
            className="flex flex-col gap-4 rounded-3xl bg-card p-5 shadow-xs ring-1 ring-border/60 sm:p-6"
        >
            <div className="flex items-center justify-between gap-4">
                <div className="flex min-w-0 flex-col gap-1">
                    <h2
                        id="calorie-goal-heading"
                        className="text-sm text-muted-foreground"
                    >
                        {t('health_entries.logbook.calorie_goal')}
                    </h2>
                    <p className="flex flex-wrap items-baseline gap-x-1">
                        <span className="text-5xl font-bold tracking-tight tabular-nums">
                            {formatAmount(calories, locale)}
                        </span>
                        <span className="text-lg text-muted-foreground tabular-nums">
                            {goals
                                ? t('health_entries.logbook.goal_of', {
                                      value: formatAmount(
                                          goals.calories,
                                          locale,
                                      ),
                                      unit: t('health_entries.nutrition.kcal'),
                                  })
                                : t('health_entries.nutrition.kcal')}
                        </span>
                    </p>
                </div>
                {goals ? (
                    <ProgressRing
                        value={calories / goals.calories}
                        size={88}
                        strokeWidth={10}
                    />
                ) : (
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-14 shrink-0 items-center justify-center rounded-2xl',
                            CALORIES.tile,
                        )}
                    >
                        <CALORIES.Icon
                            className={cn('size-7', CALORIES.icon)}
                        />
                    </span>
                )}
            </div>
            {!goals && (
                <Link
                    href={biometrics.show()}
                    className="flex items-center justify-between gap-3 rounded-2xl bg-primary/5 px-4 py-3 text-sm font-medium text-primary ring-1 ring-primary/20 transition-colors outline-none hover:bg-primary/10 focus-visible:ring-[3px] focus-visible:ring-primary/50"
                >
                    {t('health_entries.logbook.setup_goals')}
                    <ChevronRight
                        className="size-4 shrink-0"
                        aria-hidden="true"
                    />
                </Link>
            )}
        </section>
    );
}
