import DashboardDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/DashboardDiabetesLogController';
import DestroyDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/DestroyDiabetesLogController';
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
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    DiabetesLogEntry,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { BarChart3, Pencil, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import DiabetesLogDialog from './diabetes-log-dialog';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('diabetes_log.index_page.title'),
        href: ListDiabetesLogController().url,
    },
];

interface Props {
    logs: {
        data: DiabetesLogEntry[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications: RecentMedication[];
    recentInsulins: RecentInsulin[];
    todaysMeals: TodaysMeal[];
}

export default function DiabetesLogIndex({
    logs,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: Props) {
    const { t } = useTranslation('common');
    const createModal = useModalToggle();
    const editModal = useModalValueToggle<DiabetesLogEntry>();

    const handleDelete = (id: number) => {
        if (confirm(t('diabetes_log.index_page.delete_confirm'))) {
            router.delete(DestroyDiabetesLogController(id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('diabetes_log.index_page.title')} />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {t('diabetes_log.index_page.title')}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('diabetes_log.index_page.description')}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={DashboardDiabetesLogController().url}
                                >
                                    <BarChart3 className="mr-2 size-4" />
                                    {t(
                                        'diabetes_log.index_page.view_dashboard',
                                    )}
                                </Link>
                            </Button>
                            <Button onClick={() => createModal.open()}>
                                <Plus className="mr-2 size-4" />
                                {t('diabetes_log.index_page.add_entry')}
                            </Button>
                        </div>
                    </div>

                    <DiabetesLogDialog
                        mode="create"
                        open={createModal.isOpen}
                        onOpenChange={(open: boolean) =>
                            open ? createModal.open() : createModal.close()
                        }
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        glucoseUnit={glucoseUnit}
                        recentMedications={recentMedications}
                        recentInsulins={recentInsulins}
                        todaysMeals={todaysMeals}
                    />

                    <DiabetesLogDialog
                        mode="edit"
                        open={editModal.isOpen}
                        onOpenChange={(open: boolean) =>
                            open
                                ? editModal.open(editModal.state!)
                                : editModal.close()
                        }
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        glucoseUnit={glucoseUnit}
                        recentMedications={recentMedications}
                        recentInsulins={recentInsulins}
                        todaysMeals={todaysMeals}
                        logEntry={editModal.state ?? undefined}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {t('diabetes_log.index_page.your_log_entries')}
                            </CardTitle>
                            <CardDescription>
                                {t('diabetes_log.index_page.recent_entries')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {logs.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    {t('diabetes_log.index_page.no_entries')}
                                </div>
                            ) : (
                                <InfiniteScroll
                                    data="logs"
                                    preserveUrl
                                    onlyNext
                                >
                                    <ul className="space-y-4">
                                        {logs.data.map((log) => (
                                            <li
                                                key={log.id}
                                                className="flex items-start justify-between rounded-lg border p-4"
                                            >
                                                <div className="space-y-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {log.glucose_value && (
                                                            <>
                                                                <span className="text-2xl font-bold">
                                                                    {
                                                                        log.glucose_value
                                                                    }
                                                                </span>
                                                                <span className="text-sm text-muted-foreground">
                                                                    {
                                                                        glucoseUnit
                                                                    }
                                                                </span>
                                                                {log.glucose_reading_type && (
                                                                    <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                                                        {
                                                                            log.glucose_reading_type
                                                                        }
                                                                    </span>
                                                                )}
                                                            </>
                                                        )}
                                                        {log.insulin_units && (
                                                            <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                                                {
                                                                    log.insulin_units
                                                                }
                                                                {t(
                                                                    'diabetes_log.index_page.units_label',
                                                                )}{' '}
                                                                {
                                                                    log.insulin_type
                                                                }
                                                            </span>
                                                        )}
                                                        {log.weight && (
                                                            <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                                                {log.weight}{' '}
                                                                {t(
                                                                    'diabetes_log.index_page.lbs_label',
                                                                )}
                                                            </span>
                                                        )}
                                                        {log.blood_pressure_systolic &&
                                                            log.blood_pressure_diastolic && (
                                                                <span className="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                                                    {
                                                                        log.blood_pressure_systolic
                                                                    }
                                                                    /
                                                                    {
                                                                        log.blood_pressure_diastolic
                                                                    }{' '}
                                                                    {t(
                                                                        'diabetes_log.index_page.bp_label',
                                                                    )}
                                                                </span>
                                                            )}
                                                    </div>
                                                    <time
                                                        className="block text-sm text-muted-foreground"
                                                        dateTime={
                                                            log.measured_at
                                                        }
                                                    >
                                                        {new Date(
                                                            log.measured_at,
                                                        ).toLocaleString()}
                                                    </time>
                                                    {log.notes && (
                                                        <p className="text-sm">
                                                            {log.notes}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            editModal.open(log)
                                                        }
                                                        aria-label={t(
                                                            'diabetes_log.index_page.edit_aria_label',
                                                        )}
                                                    >
                                                        <Pencil
                                                            className="size-4"
                                                            aria-hidden="true"
                                                        />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            handleDelete(log.id)
                                                        }
                                                        aria-label={t(
                                                            'diabetes_log.index_page.delete_aria_label',
                                                        )}
                                                    >
                                                        <Trash2
                                                            className="size-4 text-red-500"
                                                            aria-hidden="true"
                                                        />
                                                    </Button>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </InfiniteScroll>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
