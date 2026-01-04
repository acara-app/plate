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
import { useTranslation } from 'react-i18next';
import CorrelationChart from './correlation-chart';
import DashboardSummaryCards from './dashboard-summary-cards';
import DiabetesLogDialog from './diabetes-log-dialog';
import GlucoseChart from './glucose-chart';
import TimePeriodFilter from './time-period-filter';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('diabetes_log.index_page.title'),
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

    const { t } = useTranslation('common');
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
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('diabetes_log.tracking_page.title')} />
            <AdminPageWrap variant="full">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {t('diabetes_log.tracking_page.title')}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('diabetes_log.tracking_page.description')}
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
                                {t('diabetes_log.tracking_page.add_entry')}
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
                            <CardTitle>
                                {t(
                                    'diabetes_log.tracking_page.time_period_title',
                                )}
                            </CardTitle>
                            <CardDescription>
                                {t(
                                    'diabetes_log.tracking_page.time_period_description',
                                )}
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
                                        {t(
                                            'diabetes_log.tracking_page.no_entries_title',
                                        )}
                                    </h3>
                                    <p className="text-muted-foreground">
                                        {t(
                                            'diabetes_log.tracking_page.no_entries_description',
                                        )}
                                    </p>
                                    <Button onClick={() => createModal.open()}>
                                        <Plus className="mr-2 size-4" />
                                        {t(
                                            'diabetes_log.tracking_page.add_first_entry',
                                        )}
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
                                    <CardTitle>
                                        {t(
                                            'diabetes_log.tracking_page.summary_tips_title',
                                        )}
                                    </CardTitle>
                                    <CardDescription>
                                        {t(
                                            'diabetes_log.tracking_page.summary_tips_description',
                                        )}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3 text-sm">
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                {t(
                                                    'diabetes_log.tracking_page.total_entries',
                                                )}
                                            </span>
                                            <span className="font-medium">
                                                {logs.length}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                {t(
                                                    'diabetes_log.tracking_page.time_period_label',
                                                )}
                                            </span>
                                            <span className="font-medium">
                                                {t(
                                                    `diabetes_log.tracking_page.time_periods.${timePeriod}`,
                                                )}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                {t(
                                                    'diabetes_log.tracking_page.data_types_logged',
                                                )}
                                            </span>
                                            <span className="font-medium">
                                                {[
                                                    dataTypes.hasGlucose &&
                                                        t(
                                                            'diabetes_log.tracking_page.data_types.glucose',
                                                        ),
                                                    dataTypes.hasInsulin &&
                                                        t(
                                                            'diabetes_log.tracking_page.data_types.insulin',
                                                        ),
                                                    dataTypes.hasCarbs &&
                                                        t(
                                                            'diabetes_log.tracking_page.data_types.carbs',
                                                        ),
                                                    dataTypes.hasExercise &&
                                                        t(
                                                            'diabetes_log.tracking_page.data_types.exercise',
                                                        ),
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ') ||
                                                    t(
                                                        'diabetes_log.tracking_page.data_types.none',
                                                    )}
                                            </span>
                                        </div>
                                        <div className="space-y-2 pt-2">
                                            <p
                                                className="text-xs text-muted-foreground"
                                                dangerouslySetInnerHTML={{
                                                    __html: t(
                                                        'diabetes_log.tracking_page.tips.general',
                                                    ),
                                                }}
                                            />
                                            {dataTypes.hasGlucose &&
                                                !dataTypes.hasInsulin &&
                                                !dataTypes.hasCarbs && (
                                                    <p
                                                        className="text-xs text-muted-foreground"
                                                        dangerouslySetInnerHTML={{
                                                            __html: t(
                                                                'diabetes_log.tracking_page.tips.log_carbs_insulin',
                                                            ),
                                                        }}
                                                    />
                                                )}
                                            {!dataTypes.hasExercise && (
                                                <p
                                                    className="text-xs text-muted-foreground"
                                                    dangerouslySetInnerHTML={{
                                                        __html: t(
                                                            'diabetes_log.tracking_page.tips.log_exercise',
                                                        ),
                                                    }}
                                                />
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
