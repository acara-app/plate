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
import type { DiabetesTrackingPageProps } from '@/types/diabetes';
import { Head, Link, usePage } from '@inertiajs/react';
import { List, Plus } from 'lucide-react';
import CorrelationChart from './correlation-chart';
import DashboardSummaryCards from './dashboard-summary-cards';
import DiabetesLogDialog from './diabetes-log-dialog';
import GlucoseChart from './glucose-chart';
import TimePeriodFilter from './time-period-filter';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Diabetes Log',
        href: ListDiabetesLogController().url,
    },
];

export default function DiabetesLogDashboard() {
    const {
        logs,
        timePeriod,
        summary,
        glucoseReadingTypes,
        insulinTypes,
        glucoseUnit,
        recentMedications,
        recentInsulins,
        todaysMeals,
    } = usePage<DiabetesTrackingPageProps>().props;

    const createModal = useModalToggle();

    // Transform logs to format expected by GlucoseChart (for glucose data only)
    const glucoseReadings = logs
        .filter((log) => log.glucose_value !== null)
        .map((log) => ({
            id: log.id,
            reading_value: log.glucose_value!,
            reading_type: log.glucose_reading_type || 'random',
            measured_at: log.measured_at,
            notes: log.notes,
            created_at: log.created_at,
        }));

    const { dataTypes } = summary;

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
                            <TimePeriodFilter />
                        </CardContent>
                    </Card>

                    {/* Check if there are logs */}
                    {logs.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center">
                                <div className="mx-auto max-w-md space-y-4">
                                    <h3 className="text-lg font-semibold">
                                        No entries in this period
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Try selecting a different time period or
                                        add more entries to see analytics.
                                    </p>
                                    <Button onClick={() => createModal.open()}>
                                        <Plus className="mr-2 size-4" />
                                        Add Your First Entry
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <>
                            {/* Summary Cards for All Metrics */}
                            <DashboardSummaryCards />

                            {/* Correlation Chart (shows when we have multiple factors) */}
                            {dataTypes.hasMultipleFactors && (
                                <CorrelationChart />
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
                                                {logs.length}
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
                                                    dataTypes.hasGlucose &&
                                                        'Glucose',
                                                    dataTypes.hasInsulin &&
                                                        'Insulin',
                                                    dataTypes.hasCarbs &&
                                                        'Carbs',
                                                    dataTypes.hasExercise &&
                                                        'Exercise',
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
                                            {dataTypes.hasGlucose &&
                                                !dataTypes.hasInsulin &&
                                                !dataTypes.hasCarbs && (
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
                                            {!dataTypes.hasExercise && (
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
