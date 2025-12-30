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
import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { BarChart3, Pencil, Plus, Trash2 } from 'lucide-react';
import DiabetesLogDialog from './diabetes-log-dialog';

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
    const createModal = useModalToggle();
    const editModal = useModalValueToggle<DiabetesLogEntry>();

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this entry?')) {
            router.delete(DestroyDiabetesLogController(id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Diabetes Log" />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Diabetes Log
                            </h1>
                            <p className="text-muted-foreground">
                                Track and monitor your diabetes management
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={DashboardDiabetesLogController().url}
                                >
                                    <BarChart3 className="mr-2 size-4" />
                                    View Dashboard
                                </Link>
                            </Button>
                            <Button onClick={() => createModal.open()}>
                                <Plus className="mr-2 size-4" />
                                Add Entry
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
                            <CardTitle>Your Log Entries</CardTitle>
                            <CardDescription>
                                Recent diabetes log entries
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {logs.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    No log entries yet. Add your first entry to
                                    get started.
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
                                                                u{' '}
                                                                {
                                                                    log.insulin_type
                                                                }
                                                            </span>
                                                        )}
                                                        {log.weight && (
                                                            <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                                                {log.weight} lbs
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
                                                                    BP
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
                                                        aria-label={`Edit log entry`}
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
                                                        aria-label={`Delete log entry`}
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
