import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ShowHealthEntryController';
import AppLayout from '@/layouts/app-layout';
import {
    entryTitle,
    getPrimaryType,
    hasFood,
} from '@/lib/health-entry-registry';
import { type BreadcrumbItem } from '@/types';
import {
    HealthEntry,
    LogTypeValue,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import MealDetail from './components/meal-detail';
import ReadingDetail from './components/reading-detail';
import HealthEntriesDialog from './health-entries-dialog';

interface Props {
    entry: HealthEntry;
    date: string;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
}

export default function HealthEntryShow({
    entry,
    date,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: Props) {
    const { t } = useTranslation('common');
    const [editOpen, setEditOpen] = useState(false);
    const [editType, setEditType] = useState<LogTypeValue>(() =>
        getPrimaryType(entry),
    );
    const backUrl = ListHealthEntryController({ query: { date } }).url;
    const title = entryTitle(entry, t);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('health_entries.index_page.title'),
            href: backUrl,
        },
        {
            title,
            href: ShowHealthEntryController(entry.id).url,
        },
    ];

    const editReading = (type: LogTypeValue) => {
        setEditType(type);
        setEditOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            {hasFood(entry) ? (
                <MealDetail
                    entry={entry}
                    backUrl={backUrl}
                    glucoseUnit={glucoseUnit}
                    onEditReading={editReading}
                />
            ) : (
                <ReadingDetail
                    entry={entry}
                    backUrl={backUrl}
                    glucoseUnit={glucoseUnit}
                    onEdit={editReading}
                />
            )}
            <HealthEntriesDialog
                mode="edit"
                open={editOpen}
                onOpenChange={setEditOpen}
                logEntry={entry}
                type={editType}
                glucoseReadingTypes={glucoseReadingTypes}
                insulinTypes={insulinTypes}
                glucoseUnit={glucoseUnit}
                recentMedications={recentMedications}
                recentInsulins={recentInsulins}
                todaysMeals={todaysMeals}
            />
        </AppLayout>
    );
}
