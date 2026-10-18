import DestroyHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/DestroyHealthEntryController';
import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import AdminPageWrap from '@/components/sections/admin-page-wrap';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import { formatLocalDatetime } from '@/lib/format-local-datetime';
import { HEALTH_ENTRY_TYPES } from '@/lib/health-entry-registry';
import { type BreadcrumbItem } from '@/types';
import {
    HealthEntry,
    LogTypeValue,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { BookOpen, Camera, Plus } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import EntryRow from './components/entry-row';
import HealthEntriesDialog from './health-entries-dialog';

interface Props {
    logs: {
        data: HealthEntry[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: { type: LogTypeValue | null };
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
}

export default function HealthEntriesIndex({
    logs,
    filters,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: Props) {
    const { t, i18n } = useTranslation('common');
    const createModal = useModalToggle();
    const editModal = useModalValueToggle<HealthEntry>();
    const [deleteEntry, setDeleteEntry] = useState<HealthEntry | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [filtering, setFiltering] = useState(false);
    const today = formatLocalDatetime(new Date()).slice(0, 10);
    const yesterdayDate = new Date();
    yesterdayDate.setDate(yesterdayDate.getDate() - 1);
    const yesterday = formatLocalDatetime(yesterdayDate).slice(0, 10);
    const helpers = {
        glucoseReadingTypes,
        insulinTypes,
        glucoseUnit,
        recentMedications,
        recentInsulins,
        todaysMeals,
    };
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('health_entries.index_page.title'),
            href: ListHealthEntryController().url,
        },
    ];

    const filterBy = (value: string) => {
        if (!value || value === (filters.type ?? 'all')) {
            return;
        }
        router.visit(
            ListHealthEntryController({
                query: value === 'all' ? {} : { type: value },
            }),
            {
                only: ['logs', 'filters'],
                reset: ['logs'],
                preserveState: true,
                onStart: () => setFiltering(true),
                onFinish: () => setFiltering(false),
            },
        );
    };

    const removeEntry = () => {
        if (!deleteEntry) {
            return;
        }
        router.delete(DestroyHealthEntryController(deleteEntry.id).url, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onSuccess: () => setDeleteEntry(null),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('health_entries.index_page.title')} />
            <AdminPageWrap variant="md">
                <div className="flex min-w-0 flex-col gap-8 pb-12 sm:gap-10">
                    <header className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-col gap-2">
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {t('health_entries.index_page.title')}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {t('health_entries.index_page.description')}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                asChild
                                variant="secondary"
                                className="h-11"
                            >
                                <Link href={ShowSnapToTrackController()}>
                                    <Camera aria-hidden="true" />
                                    {t('health_entries.timeline.scan_meal')}
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                className="h-11"
                                onClick={createModal.open}
                            >
                                <Plus aria-hidden="true" />
                                {t('health_entries.index_page.add_entry')}
                            </Button>
                        </div>
                    </header>
                    <div className="-mx-1 overflow-x-auto px-1 pb-1">
                        <ToggleGroup
                            type="single"
                            value={filters.type ?? 'all'}
                            onValueChange={filterBy}
                            disabled={filtering}
                            className="w-max flex-nowrap justify-start gap-1"
                            aria-label={t(
                                'health_entries.timeline.filter_label',
                            )}
                        >
                            <ToggleGroupItem value="all" className="h-11 px-4">
                                {t('health_entries.timeline.all')}
                            </ToggleGroupItem>
                            {HEALTH_ENTRY_TYPES.toSorted(
                                (a, b) =>
                                    Number(b.key === 'food') -
                                    Number(a.key === 'food'),
                            ).map(({ key, labelKey }) => (
                                <ToggleGroupItem
                                    key={key}
                                    value={key}
                                    className="h-11 px-4"
                                >
                                    {t(labelKey)}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                    </div>
                    <div aria-busy={filtering}>
                        {logs.data.length === 0 ? (
                            <div className="flex flex-col items-center gap-4 py-16 text-center">
                                <BookOpen
                                    className="size-8 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <h2 className="text-lg font-medium">
                                    {t(
                                        filters.type
                                            ? 'health_entries.timeline.no_matches'
                                            : 'health_entries.timeline.empty_title',
                                    )}
                                </h2>
                                <p className="max-w-sm text-sm text-muted-foreground">
                                    {t(
                                        filters.type
                                            ? 'health_entries.timeline.no_matches_description'
                                            : 'health_entries.index_page.no_entries',
                                    )}
                                </p>
                                {filters.type && (
                                    <Button
                                        variant="secondary"
                                        onClick={() => filterBy('all')}
                                    >
                                        {t('health_entries.timeline.show_all')}
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <InfiniteScroll
                                data="logs"
                                itemsElement="#health-entry-list"
                                preserveUrl
                                onlyNext
                                loading={
                                    <p
                                        role="status"
                                        className="py-6 text-center text-sm text-muted-foreground"
                                    >
                                        {t('health_entries.timeline.loading')}
                                    </p>
                                }
                            >
                                <ol
                                    id="health-entry-list"
                                    className="flex flex-col"
                                >
                                    {logs.data.map((entry, index) => {
                                        const day = formatLocalDatetime(
                                            new Date(entry.measured_at),
                                        ).slice(0, 10);
                                        const previousDay =
                                            index > 0
                                                ? formatLocalDatetime(
                                                      new Date(
                                                          logs.data[index - 1]
                                                              .measured_at,
                                                      ),
                                                  ).slice(0, 10)
                                                : null;
                                        const dayLabel =
                                            day === today
                                                ? t(
                                                      'health_entries.timeline.today',
                                                  )
                                                : day === yesterday
                                                  ? t(
                                                        'health_entries.timeline.yesterday',
                                                    )
                                                  : new Date(
                                                        entry.measured_at,
                                                    ).toLocaleDateString(
                                                        i18n.resolvedLanguage,
                                                        {
                                                            weekday: 'short',
                                                            month: 'long',
                                                            day: 'numeric',
                                                            year: 'numeric',
                                                        },
                                                    );
                                        return (
                                            <li key={entry.id}>
                                                {day !== previousDay && (
                                                    <h2
                                                        className={
                                                            index === 0
                                                                ? 'pb-3 text-sm font-semibold'
                                                                : 'pt-8 pb-3 text-sm font-semibold'
                                                        }
                                                    >
                                                        {dayLabel}
                                                    </h2>
                                                )}
                                                <EntryRow
                                                    entry={entry}
                                                    glucoseUnit={glucoseUnit}
                                                    onEdit={() =>
                                                        editModal.open(entry)
                                                    }
                                                    onDelete={() =>
                                                        setDeleteEntry(entry)
                                                    }
                                                />
                                            </li>
                                        );
                                    })}
                                </ol>
                            </InfiniteScroll>
                        )}
                    </div>
                </div>
                <HealthEntriesDialog
                    mode="create"
                    open={createModal.isOpen}
                    onOpenChange={(open) =>
                        open ? createModal.open() : createModal.close()
                    }
                    {...helpers}
                />
                <HealthEntriesDialog
                    mode="edit"
                    open={editModal.isOpen}
                    onOpenChange={(open) => !open && editModal.close()}
                    logEntry={editModal.state ?? undefined}
                    {...helpers}
                />
                <AlertDialog
                    open={deleteEntry !== null}
                    onOpenChange={(open) =>
                        !open && !deleting && setDeleteEntry(null)
                    }
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {t('health_entries.timeline.delete_title')}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {t(
                                    deleteEntry?.photo_url
                                        ? 'health_entries.timeline.delete_photo_description'
                                        : 'health_entries.timeline.delete_description',
                                )}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel disabled={deleting}>
                                {t('health_entries.common.cancel')}
                            </AlertDialogCancel>
                            <Button
                                variant="destructive"
                                disabled={deleting}
                                onClick={removeEntry}
                            >
                                {t(
                                    deleting
                                        ? 'health_entries.timeline.deleting'
                                        : 'health_entries.timeline.delete',
                                )}
                            </Button>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </AdminPageWrap>
        </AppLayout>
    );
}
