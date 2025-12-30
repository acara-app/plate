import ListDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/ListDiabetesLogController';
import AdminPageWrap from '@/components/sections/admin-page-wrap';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import useModalToggle from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type GlucoseUnitType } from '@/types/glucose';
import { Head, Link } from '@inertiajs/react';
import { List, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import CorrelationChart from './correlation-chart';
import DashboardSummaryCards from './dashboard-summary-cards';
import DiabetesLogDialog from './diabetes-log-dialog';
import GlucoseChart from './glucose-chart';
import TimePeriodFilter, { type TimePeriod } from './time-period-filter';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Diabetes Log',
        href: ListDiabetesLogController().url,
    },
];

interface ReadingType {
    value: string;
    label: string;
}

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

interface RecentMedication {
    name: string;
    dosage: string;
    label: string;
}

interface RecentInsulin {
    units: number;
    type: string;
    label: string;
}

interface TodaysMeal {
    id: number;
    name: string;
    type: string;
    carbs: number;
    label: string;
}

interface Props {
    logs: DiabetesLogEntry[];
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: GlucoseUnitType;
    recentMedications: RecentMedication[];
    recentInsulins: RecentInsulin[];
    todaysMeals: TodaysMeal[];
}

function filterLogsByPeriod(
    logs: DiabetesLogEntry[],
    period: TimePeriod,
): DiabetesLogEntry[] {
    const now = new Date();
    const daysMap: Record<TimePeriod, number> = {
        '7d': 7,
        '30d': 30,
        '90d': 90,
    };

    const days = daysMap[period];
    const cutoffDate = new Date(now);
    cutoffDate.setDate(cutoffDate.getDate() - days);

    return logs.filter((log) => {
        const logDate = new Date(log.measured_at);
        return logDate >= cutoffDate;
    });
}

export default function DiabetesLogDashboard({
    logs,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: Props) {
    const [timePeriod, setTimePeriod] = useState<TimePeriod>('30d');
    const createModal = useModalToggle();

    const filteredLogs = useMemo(
        () => filterLogsByPeriod(logs, timePeriod),
        [logs, timePeriod],
    );

    // Transform logs to format expected by GlucoseChart (for glucose data only)
    const glucoseReadings = filteredLogs
        .filter((log) => log.glucose_value !== null)
        .map((log) => ({
            id: log.id,
            reading_value: log.glucose_value!,
            reading_type: log.glucose_reading_type || 'random',
            measured_at: log.measured_at,
            notes: log.notes,
            created_at: log.created_at,
        }));

    // Check what types of data we have
    const hasGlucose = filteredLogs.some((log) => log.glucose_value !== null);
    const hasInsulin = filteredLogs.some((log) => log.insulin_units !== null);
    const hasCarbs = filteredLogs.some((log) => log.carbs_grams !== null);
    const hasExercise = filteredLogs.some(
        (log) => log.exercise_duration_minutes !== null,
    );
    const hasMultipleFactors =
        [hasGlucose, hasInsulin, hasCarbs, hasExercise].filter(Boolean).length >
        1;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Diabetes Log Dashboard" />
            <AdminPageWrap variant="full">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Diabetes Log Dashboard
                            </h1>
                            <p className="text-muted-foreground">
                                Comprehensive analytics and trends for your
                                diabetes management
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="icon" asChild>
                                <Link href={ListDiabetesLogController().url}>
                                    <List className="size-4" />
                                </Link>
                            </Button>
                            <Button onClick={createModal.open}>
                                <Plus className="mr-2 size-4" />
                                Add Entry
                            </Button>
                        </div>
                    </div>

                    {/* Add Entry Dialog */}
                    <DiabetesLogDialog
                        mode="create"
                        open={createModal.isOpen}
                        onOpenChange={(open) =>
                            open ? createModal.open() : createModal.close()
                        }
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        glucoseUnit={glucoseUnit}
                        recentMedications={recentMedications}
                        recentInsulins={recentInsulins}
                        todaysMeals={todaysMeals}
                    />

                    {/* Time Period Filter */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Time Period</CardTitle>
                            <CardDescription>
                                Select the time range for your analytics
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <TimePeriodFilter
                                selected={timePeriod}
                                onChange={setTimePeriod}
                            />
                        </CardContent>
                    </Card>

                    {/* Check if there are logs */}
                    {logs.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center">
                                <div className="mx-auto max-w-md space-y-4">
                                    <h3 className="text-lg font-semibold">
                                        No log entries yet
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Start tracking your diabetes data to see
                                        comprehensive analytics and insights.
                                    </p>
                                    <Button onClick={() => createModal.open()}>
                                        <Plus className="mr-2 size-4" />
                                        Add Your First Entry
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ) : filteredLogs.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center">
                                <div className="mx-auto max-w-md space-y-4">
                                    <h3 className="text-lg font-semibold">
                                        No entries in this period
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Try selecting a different time period or
                                        add more entries.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <>
                            {/* Summary Cards for All Metrics */}
                            <DashboardSummaryCards
                                logs={filteredLogs}
                                glucoseUnit={glucoseUnit}
                            />

                            {/* Correlation Chart (shows when we have multiple factors) */}
                            {hasMultipleFactors && (
                                <CorrelationChart
                                    logs={filteredLogs}
                                    glucoseUnit={glucoseUnit}
                                />
                            )}

                            {/* Glucose Chart (if there are glucose readings) */}
                            {glucoseReadings.length > 0 && (
                                <GlucoseChart
                                    readings={glucoseReadings}
                                    glucoseUnit={glucoseUnit}
                                />
                            )}

                            {/* Insights Card */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Summary & Tips</CardTitle>
                                    <CardDescription>
                                        Key insights from your diabetes log
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3 text-sm">
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Total Entries
                                            </span>
                                            <span className="font-medium">
                                                {filteredLogs.length}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Time Period
                                            </span>
                                            <span className="font-medium">
                                                {timePeriod === '7d'
                                                    ? 'Last 7 Days'
                                                    : timePeriod === '30d'
                                                      ? 'Last 30 Days'
                                                      : 'Last 90 Days'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Data Types Logged
                                            </span>
                                            <span className="font-medium">
                                                {[
                                                    hasGlucose && 'Glucose',
                                                    hasInsulin && 'Insulin',
                                                    hasCarbs && 'Carbs',
                                                    hasExercise && 'Exercise',
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ') || 'None'}
                                            </span>
                                        </div>
                                        <div className="space-y-2 pt-2">
                                            <p className="text-xs text-muted-foreground">
                                                💡 <strong>Tip:</strong> Log
                                                multiple factors (glucose,
                                                insulin, carbs, exercise) to see
                                                correlations and understand how
                                                they affect each other.
                                            </p>
                                            {hasGlucose &&
                                                !hasInsulin &&
                                                !hasCarbs && (
                                                    <p className="text-xs text-muted-foreground">
                                                        📊{' '}
                                                        <strong>
                                                            Suggestion:
                                                        </strong>{' '}
                                                        Try logging your carb
                                                        intake and insulin doses
                                                        to see their impact on
                                                        glucose levels.
                                                    </p>
                                                )}
                                            {!hasExercise && (
                                                <p className="text-xs text-muted-foreground">
                                                    🏃{' '}
                                                    <strong>Suggestion:</strong>{' '}
                                                    Log your exercise sessions
                                                    to see how physical activity
                                                    affects your glucose
                                                    control.
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </>
                    )}
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
