import DestroyHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/DestroyHealthEntryController';
import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
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
    HealthEntry,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Head, InfiniteScroll, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import EntryValueBadges from './components/entry-value-badges';
import SourceBadge from './components/source-badge';
import HealthEntriesDialog from './health-entries-dialog';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('health_entries.index_page.title'),
        href: ListHealthEntryController().url,
    },
];

interface Props {
    logs: {
        data: HealthEntry[];
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

export default function HealthEntriesIndex({
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
    const editModal = useModalValueToggle<HealthEntry>();

    const handleDelete = (id: number) => {
        if (confirm(t('health_entries.index_page.delete_confirm'))) {
            router.delete(DestroyHealthEntryController(id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('health_entries.index_page.title')} />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {t('health_entries.index_page.title')}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('health_entries.index_page.description')}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={() => createModal.open()}>
                                <Plus className="mr-2 size-4" />
                                {t('health_entries.index_page.add_entry')}
                            </Button>
                        </div>
                    </div>

                    <HealthEntriesDialog
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

                    <HealthEntriesDialog
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
                                {t(
                                    'health_entries.index_page.your_log_entries',
                                )}
                            </CardTitle>
                            <CardDescription>
                                {t('health_entries.index_page.recent_entries')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {logs.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    {t('health_entries.index_page.no_entries')}
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
                                                <div className="space-y-1.5">
                                                    <EntryValueBadges
                                                        entry={log}
                                                        glucoseUnit={
                                                            glucoseUnit
                                                        }
                                                    />
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <time
                                                            className="text-sm text-muted-foreground"
                                                            dateTime={
                                                                log.measured_at
                                                            }
                                                        >
                                                            {new Date(
                                                                log.measured_at,
                                                            ).toLocaleString()}
                                                        </time>
                                                        <SourceBadge
                                                            source={log.source}
                                                        />
                                                    </div>
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
                                                            'health_entries.index_page.edit_aria_label',
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
                                                            'health_entries.index_page.delete_aria_label',
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
