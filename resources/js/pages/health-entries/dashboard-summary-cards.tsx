import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { convertGlucoseValue } from '@/lib/utils';
import { type DiabetesTrackingPageProps } from '@/types/diabetes';
import { usePage } from '@inertiajs/react';
import {
    Activity,
    Droplet,
    Flame,
    Heart,
    Pill,
    Scale,
    Syringe,
    TrendingDown,
    TrendingUp,
    Utensils,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface StatCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: React.ReactNode;
    trend?: 'up' | 'down' | 'stable';
    trendValue?: string;
    colorClass?: string;
}

function StatCard({
    title,
    value,
    subtitle,
    icon,
    trend,
    trendValue,
    colorClass = 'text-primary',
}: StatCardProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <div className={colorClass}>{icon}</div>
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {subtitle && (
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
                )}
                {trend && trendValue && (
                    <div className="mt-1 flex items-center text-xs">
                        {trend === 'up' ? (
                            <TrendingUp className="mr-1 size-3 text-red-500" />
                        ) : trend === 'down' ? (
                            <TrendingDown className="mr-1 size-3 text-green-500" />
                        ) : null}
                        <span
                            className={
                                trend === 'up'
                                    ? 'text-red-500'
                                    : trend === 'down'
                                      ? 'text-green-500'
                                      : 'text-muted-foreground'
                            }
                        >
                            {trendValue}
                        </span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function DashboardSummaryCards() {
    const { t } = useTranslation('common');
    const { summary, glucoseUnit } = usePage<DiabetesTrackingPageProps>().props;

    const {
        glucoseStats,
        insulinStats,
        carbStats,
        exerciseStats,
        weightStats,
        bpStats,
        medicationStats,
        a1cStats,
        streakStats,
    } = summary;

    // Convert glucose values to user's preferred unit
    const avgGlucose = convertGlucoseValue(glucoseStats.avg, glucoseUnit);
    const minGlucose = convertGlucoseValue(glucoseStats.min, glucoseUnit);
    const maxGlucose = convertGlucoseValue(glucoseStats.max, glucoseUnit);

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <StatCard
                title={t('health_entries.summary_cards.logging_streak.title')}
                value={
                    streakStats.currentStreak > 0
                        ? t(
                              'health_entries.summary_cards.logging_streak.days',
                              {
                                  count: streakStats.currentStreak,
                              },
                          )
                        : t(
                              'health_entries.summary_cards.logging_streak.start_today',
                          )
                }
                subtitle={t(
                    'health_entries.summary_cards.logging_streak.active_days',
                    { count: streakStats.activeDays },
                )}
                icon={<Flame className="size-4" />}
                colorClass="text-orange-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.glucose.title')}
                value={glucoseStats.count}
                subtitle={
                    glucoseStats.count > 0
                        ? t('health_entries.summary_cards.glucose.avg_range', {
                              avg: avgGlucose,
                              unit: glucoseUnit,
                              min: minGlucose,
                              max: maxGlucose,
                          })
                        : t('health_entries.summary_cards.glucose.no_readings')
                }
                icon={<Droplet className="size-4" />}
                colorClass="text-blue-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.insulin.title')}
                value={insulinStats.count}
                subtitle={
                    insulinStats.count > 0
                        ? t('health_entries.summary_cards.insulin.total', {
                              total: insulinStats.total,
                              bolus: insulinStats.bolusCount,
                              basal: insulinStats.basalCount,
                          })
                        : t('health_entries.summary_cards.insulin.no_doses')
                }
                icon={<Syringe className="size-4" />}
                colorClass="text-purple-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.carbs.title')}
                value={
                    carbStats.count > 0
                        ? t('health_entries.summary_cards.carbs.per_day', {
                              avg: carbStats.avgPerDay,
                          })
                        : '—'
                }
                subtitle={
                    carbStats.count > 0
                        ? t('health_entries.summary_cards.carbs.total_days', {
                              total: carbStats.total,
                              days: carbStats.uniqueDays,
                          })
                        : t('health_entries.summary_cards.carbs.no_carbs')
                }
                icon={<Utensils className="size-4" />}
                colorClass="text-amber-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.exercise.title')}
                value={
                    exerciseStats.count > 0
                        ? t('health_entries.summary_cards.exercise.minutes', {
                              count: exerciseStats.totalMinutes,
                          })
                        : '—'
                }
                subtitle={
                    exerciseStats.count > 0
                        ? exerciseStats.types.length > 0
                            ? t(
                                  'health_entries.summary_cards.exercise.sessions_with_types',
                                  {
                                      count: exerciseStats.count,
                                      types: exerciseStats.types
                                          .slice(0, 2)
                                          .join(', '),
                                  },
                              )
                            : t(
                                  'health_entries.summary_cards.exercise.sessions',
                                  { count: exerciseStats.count },
                              )
                        : t('health_entries.summary_cards.exercise.no_exercise')
                }
                icon={<Activity className="size-4" />}
                colorClass="text-green-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.weight.title')}
                value={
                    weightStats.latest
                        ? t('health_entries.summary_cards.weight.lbs', {
                              value: weightStats.latest,
                          })
                        : '—'
                }
                subtitle={
                    weightStats.count > 0
                        ? t('health_entries.summary_cards.weight.entries', {
                              count: weightStats.count,
                          })
                        : t('health_entries.summary_cards.weight.no_weight')
                }
                icon={<Scale className="size-4" />}
                trend={weightStats.trend ?? undefined}
                trendValue={
                    weightStats.diff
                        ? t('health_entries.summary_cards.weight.lbs', {
                              value: weightStats.diff,
                          })
                        : undefined
                }
                colorClass="text-cyan-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.blood_pressure.title')}
                value={
                    bpStats.latestSystolic && bpStats.latestDiastolic
                        ? `${bpStats.latestSystolic}/${bpStats.latestDiastolic}`
                        : '—'
                }
                subtitle={
                    bpStats.count > 0
                        ? t(
                              'health_entries.summary_cards.blood_pressure.readings',
                              { count: bpStats.count },
                          )
                        : t('health_entries.summary_cards.blood_pressure.no_bp')
                }
                icon={<Heart className="size-4" />}
                colorClass="text-red-500"
            />

            <StatCard
                title={t('health_entries.summary_cards.medications.title')}
                value={medicationStats.count}
                subtitle={
                    medicationStats.uniqueMedications.length > 0
                        ? medicationStats.uniqueMedications
                              .slice(0, 2)
                              .join(', ')
                        : t(
                              'health_entries.summary_cards.medications.no_medications',
                          )
                }
                icon={<Pill className="size-4" />}
                colorClass="text-pink-500"
            />

            {a1cStats.latest && (
                <StatCard
                    title={t('health_entries.summary_cards.a1c.title')}
                    value={t('health_entries.summary_cards.a1c.percent', {
                        value: a1cStats.latest,
                    })}
                    subtitle={t('health_entries.summary_cards.a1c.readings', {
                        count: a1cStats.count,
                    })}
                    icon={<Droplet className="size-4" />}
                    colorClass="text-indigo-500"
                />
            )}
        </div>
    );
}
