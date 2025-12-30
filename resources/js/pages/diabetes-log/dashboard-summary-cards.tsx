import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    type DiabetesTrackingPageProps,
    GlucoseUnit,
    type GlucoseUnitType,
    MGDL_TO_MMOL_FACTOR,
} from '@/types/diabetes';
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

interface StatCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: React.ReactNode;
    trend?: 'up' | 'down' | 'stable';
    trendValue?: string;
    colorClass?: string;
}

// Conversion helper: mg/dL to mmol/L
function convertGlucose(
    value: number,
    targetUnit: GlucoseUnitType,
): { value: number; unit: GlucoseUnitType } {
    if (targetUnit === GlucoseUnit.MmolL) {
        return {
            value: Math.round((value / MGDL_TO_MMOL_FACTOR) * 10) / 10,
            unit: GlucoseUnit.MmolL,
        };
    }
    return { value: Math.round(value), unit: GlucoseUnit.MgDl };
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
    const avgGlucose = convertGlucose(glucoseStats.avg, glucoseUnit);
    const minGlucose = convertGlucose(glucoseStats.min, glucoseUnit);
    const maxGlucose = convertGlucose(glucoseStats.max, glucoseUnit);

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <StatCard
                title="Logging Streak"
                value={
                    streakStats.currentStreak > 0
                        ? `🔥 ${streakStats.currentStreak} days`
                        : 'Start today!'
                }
                subtitle={`${streakStats.activeDays} active days in this period`}
                icon={<Flame className="size-4" />}
                colorClass="text-orange-500"
            />

            <StatCard
                title="Glucose Readings"
                value={glucoseStats.count}
                subtitle={
                    glucoseStats.count > 0
                        ? `Avg: ${avgGlucose.value} ${glucoseUnit} (${minGlucose.value}-${maxGlucose.value})`
                        : 'No readings'
                }
                icon={<Droplet className="size-4" />}
                colorClass="text-blue-500"
            />

            <StatCard
                title="Insulin Doses"
                value={insulinStats.count}
                subtitle={
                    insulinStats.count > 0
                        ? `Total: ${insulinStats.total}u (${insulinStats.bolusCount} bolus, ${insulinStats.basalCount} basal)`
                        : 'No doses logged'
                }
                icon={<Syringe className="size-4" />}
                colorClass="text-purple-500"
            />

            <StatCard
                title="Daily Carbs"
                value={
                    carbStats.count > 0 ? `${carbStats.avgPerDay}g/day` : '—'
                }
                subtitle={
                    carbStats.count > 0
                        ? `${carbStats.total}g total over ${carbStats.uniqueDays} days`
                        : 'No carbs logged'
                }
                icon={<Utensils className="size-4" />}
                colorClass="text-amber-500"
            />

            <StatCard
                title="Exercise"
                value={
                    exerciseStats.count > 0
                        ? `${exerciseStats.totalMinutes} min`
                        : '—'
                }
                subtitle={
                    exerciseStats.count > 0
                        ? `${exerciseStats.count} sessions${exerciseStats.types.length > 0 ? `: ${exerciseStats.types.slice(0, 2).join(', ')}` : ''}`
                        : 'No exercise logged'
                }
                icon={<Activity className="size-4" />}
                colorClass="text-green-500"
            />

            <StatCard
                title="Weight"
                value={weightStats.latest ? `${weightStats.latest} lbs` : '—'}
                subtitle={
                    weightStats.count > 0
                        ? `${weightStats.count} entries`
                        : 'No weight logged'
                }
                icon={<Scale className="size-4" />}
                trend={weightStats.trend ?? undefined}
                trendValue={
                    weightStats.diff ? `${weightStats.diff} lbs` : undefined
                }
                colorClass="text-cyan-500"
            />

            <StatCard
                title="Blood Pressure"
                value={
                    bpStats.latestSystolic && bpStats.latestDiastolic
                        ? `${bpStats.latestSystolic}/${bpStats.latestDiastolic}`
                        : '—'
                }
                subtitle={
                    bpStats.count > 0
                        ? `${bpStats.count} readings`
                        : 'No BP logged'
                }
                icon={<Heart className="size-4" />}
                colorClass="text-red-500"
            />

            <StatCard
                title="Medications"
                value={medicationStats.count}
                subtitle={
                    medicationStats.uniqueMedications.length > 0
                        ? medicationStats.uniqueMedications
                              .slice(0, 2)
                              .join(', ')
                        : 'No medications logged'
                }
                icon={<Pill className="size-4" />}
                colorClass="text-pink-500"
            />

            {a1cStats.latest && (
                <StatCard
                    title="Latest A1C"
                    value={`${a1cStats.latest}%`}
                    subtitle={`${a1cStats.count} readings`}
                    icon={<Droplet className="size-4" />}
                    colorClass="text-indigo-500"
                />
            )}
        </div>
    );
}
