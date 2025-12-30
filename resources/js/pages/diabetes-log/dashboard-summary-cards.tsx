import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    GlucoseUnit,
    type GlucoseUnitType,
    InsulinType,
    MGDL_TO_MMOL_FACTOR,
} from '@/types/glucose';
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

interface DiabetesLogEntry {
    id: number;
    glucose_value: number | null;
    glucose_reading_type: string | null;
    measured_at: string;
    notes: string | null;
    insulin_units: number | null;
    insulin_type: string | null;
    medication_name: string | null;
    medication_dosage: string | null;
    weight: number | null;
    blood_pressure_systolic: number | null;
    blood_pressure_diastolic: number | null;
    a1c_value: number | null;
    carbs_grams: number | null;
    exercise_type: string | null;
    exercise_duration_minutes: number | null;
    created_at: string;
}

interface Props {
    logs: DiabetesLogEntry[];
    glucoseUnit: GlucoseUnitType;
}

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

// Calculate current logging streak (consecutive days)
function calculateStreak(logs: DiabetesLogEntry[]): {
    currentStreak: number;
    activeDays: number;
} {
    if (logs.length === 0) {
        return { currentStreak: 0, activeDays: 0 };
    }

    // Get unique dates with logs
    const uniqueDates = [
        ...new Set(
            logs.map(
                (log) => new Date(log.measured_at).toISOString().split('T')[0],
            ),
        ),
    ].sort((a, b) => b.localeCompare(a)); // Sort descending (newest first)

    const activeDays = uniqueDates.length;

    // Calculate streak from today/yesterday
    const today = new Date().toISOString().split('T')[0];
    const yesterday = new Date(Date.now() - 86400000)
        .toISOString()
        .split('T')[0];

    let streak = 0;
    let checkDate = new Date();

    // Start from today or yesterday if today has no logs
    if (!uniqueDates.includes(today)) {
        if (!uniqueDates.includes(yesterday)) {
            // No recent logs, streak is 0
            return { currentStreak: 0, activeDays };
        }
        checkDate = new Date(Date.now() - 86400000);
    }

    // Count consecutive days backwards
    for (let i = 0; i < 365; i++) {
        const dateStr = checkDate.toISOString().split('T')[0];
        if (uniqueDates.includes(dateStr)) {
            streak++;
            checkDate = new Date(checkDate.getTime() - 86400000);
        } else {
            break;
        }
    }

    return { currentStreak: streak, activeDays };
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

export default function DashboardSummaryCards({ logs, glucoseUnit }: Props) {
    const glucoseLogs = logs.filter((log) => log.glucose_value !== null);
    const glucoseValues = glucoseLogs.map((log) => log.glucose_value!);
    const avgGlucoseRaw =
        glucoseValues.length > 0
            ? glucoseValues.reduce((a, b) => a + b, 0) / glucoseValues.length
            : 0;
    const minGlucoseRaw =
        glucoseValues.length > 0 ? Math.min(...glucoseValues) : 0;
    const maxGlucoseRaw =
        glucoseValues.length > 0 ? Math.max(...glucoseValues) : 0;

    // Convert to user's preferred unit
    const avgGlucose = convertGlucose(avgGlucoseRaw, glucoseUnit);
    const minGlucose = convertGlucose(minGlucoseRaw, glucoseUnit);
    const maxGlucose = convertGlucose(maxGlucoseRaw, glucoseUnit);

    const { currentStreak, activeDays } = calculateStreak(logs);

    // Calculate insulin statistics
    const insulinLogs = logs.filter((log) => log.insulin_units !== null);
    const totalInsulin = insulinLogs.reduce(
        (sum, log) => sum + (log.insulin_units || 0),
        0,
    );
    const bolusCount = insulinLogs.filter(
        (log) => log.insulin_type === InsulinType.Bolus,
    ).length;
    const basalCount = insulinLogs.filter(
        (log) => log.insulin_type === InsulinType.Basal,
    ).length;

    const carbLogs = logs.filter((log) => log.carbs_grams !== null);
    const totalCarbs = carbLogs.reduce(
        (sum, log) => sum + (log.carbs_grams || 0),
        0,
    );
    // Calculate unique days with carb logs
    const uniqueCarbDays = new Set(
        carbLogs.map((log) => new Date(log.measured_at).toDateString()),
    ).size;
    // Daily average is more useful than per-entry average
    const avgCarbsPerDay =
        uniqueCarbDays > 0 ? Math.round(totalCarbs / uniqueCarbDays) : 0;

    // Calculate exercise statistics
    const exerciseLogs = logs.filter(
        (log) => log.exercise_duration_minutes !== null,
    );
    const totalExerciseMinutes = exerciseLogs.reduce(
        (sum, log) => sum + (log.exercise_duration_minutes || 0),
        0,
    );
    const exerciseTypes = [
        ...new Set(
            exerciseLogs.map((log) => log.exercise_type).filter(Boolean),
        ),
    ];

    // Calculate weight statistics
    const weightLogs = logs
        .filter((log) => log.weight !== null)
        .sort(
            (a, b) =>
                new Date(b.measured_at).getTime() -
                new Date(a.measured_at).getTime(),
        );
    const latestWeight = weightLogs[0]?.weight;
    const previousWeight = weightLogs[1]?.weight;
    const weightTrend =
        latestWeight && previousWeight
            ? latestWeight > previousWeight
                ? 'up'
                : latestWeight < previousWeight
                  ? 'down'
                  : 'stable'
            : undefined;
    const weightDiff =
        latestWeight && previousWeight
            ? Math.abs(latestWeight - previousWeight).toFixed(1)
            : undefined;

    // Calculate blood pressure statistics
    const bpLogs = logs
        .filter(
            (log) =>
                log.blood_pressure_systolic !== null &&
                log.blood_pressure_diastolic !== null,
        )
        .sort(
            (a, b) =>
                new Date(b.measured_at).getTime() -
                new Date(a.measured_at).getTime(),
        );
    const latestBP = bpLogs[0];

    // Calculate medication statistics
    const medicationLogs = logs.filter((log) => log.medication_name !== null);
    const uniqueMedications = [
        ...new Set(medicationLogs.map((log) => log.medication_name)),
    ];

    // Calculate A1C statistics
    const a1cLogs = logs
        .filter((log) => log.a1c_value !== null)
        .sort(
            (a, b) =>
                new Date(b.measured_at).getTime() -
                new Date(a.measured_at).getTime(),
        );
    const latestA1c = a1cLogs[0]?.a1c_value;

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <StatCard
                title="Logging Streak"
                value={
                    currentStreak > 0
                        ? `🔥 ${currentStreak} days`
                        : 'Start today!'
                }
                subtitle={`${activeDays} active days in this period`}
                icon={<Flame className="size-4" />}
                colorClass="text-orange-500"
            />

            <StatCard
                title="Glucose Readings"
                value={glucoseLogs.length}
                subtitle={
                    glucoseLogs.length > 0
                        ? `Avg: ${avgGlucose.value} ${glucoseUnit} (${minGlucose.value}-${maxGlucose.value})`
                        : 'No readings'
                }
                icon={<Droplet className="size-4" />}
                colorClass="text-blue-500"
            />

            <StatCard
                title="Insulin Doses"
                value={insulinLogs.length}
                subtitle={
                    insulinLogs.length > 0
                        ? `Total: ${totalInsulin}u (${bolusCount} bolus, ${basalCount} basal)`
                        : 'No doses logged'
                }
                icon={<Syringe className="size-4" />}
                colorClass="text-purple-500"
            />

            <StatCard
                title="Daily Carbs"
                value={carbLogs.length > 0 ? `${avgCarbsPerDay}g/day` : '—'}
                subtitle={
                    carbLogs.length > 0
                        ? `${totalCarbs}g total over ${uniqueCarbDays} days`
                        : 'No carbs logged'
                }
                icon={<Utensils className="size-4" />}
                colorClass="text-amber-500"
            />

            <StatCard
                title="Exercise"
                value={
                    exerciseLogs.length > 0
                        ? `${totalExerciseMinutes} min`
                        : '—'
                }
                subtitle={
                    exerciseLogs.length > 0
                        ? `${exerciseLogs.length} sessions${exerciseTypes.length > 0 ? `: ${exerciseTypes.slice(0, 2).join(', ')}` : ''}`
                        : 'No exercise logged'
                }
                icon={<Activity className="size-4" />}
                colorClass="text-green-500"
            />

            <StatCard
                title="Weight"
                value={latestWeight ? `${latestWeight} lbs` : '—'}
                subtitle={
                    weightLogs.length > 0
                        ? `${weightLogs.length} entries`
                        : 'No weight logged'
                }
                icon={<Scale className="size-4" />}
                trend={weightTrend as 'up' | 'down' | 'stable' | undefined}
                trendValue={weightDiff ? `${weightDiff} lbs` : undefined}
                colorClass="text-cyan-500"
            />

            <StatCard
                title="Blood Pressure"
                value={
                    latestBP
                        ? `${latestBP.blood_pressure_systolic}/${latestBP.blood_pressure_diastolic}`
                        : '—'
                }
                subtitle={
                    bpLogs.length > 0
                        ? `${bpLogs.length} readings`
                        : 'No BP logged'
                }
                icon={<Heart className="size-4" />}
                colorClass="text-red-500"
            />

            <StatCard
                title="Medications"
                value={medicationLogs.length}
                subtitle={
                    uniqueMedications.length > 0
                        ? uniqueMedications.slice(0, 2).join(', ')
                        : 'No medications logged'
                }
                icon={<Pill className="size-4" />}
                colorClass="text-pink-500"
            />

            {latestA1c && (
                <StatCard
                    title="Latest A1C"
                    value={`${latestA1c}%`}
                    subtitle={`${a1cLogs.length} readings`}
                    icon={<Droplet className="size-4" />}
                    colorClass="text-indigo-500"
                />
            )}
        </div>
    );
}
