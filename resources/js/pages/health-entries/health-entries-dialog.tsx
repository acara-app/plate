import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    HealthEntry,
    LogTypeValue,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import HealthEntryForm from './health-entry-form';

interface DialogProps {
    mode: 'create' | 'edit';
    open: boolean;
    onOpenChange: (open: boolean) => void;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
    logEntry?: HealthEntry;
    type?: LogTypeValue;
    defaultMeasuredAt?: string;
}

export default function HealthEntriesDialog({
    mode,
    open,
    onOpenChange,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications = [],
    recentInsulins = [],
    todaysMeals = [],
    logEntry,
    type,
    defaultMeasuredAt,
}: DialogProps) {
    const { t } = useTranslation('common');
    if (mode === 'edit' && !logEntry) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                closeLabel={t('health_entries.timeline.close')}
                {...(mode === 'create' && { 'aria-describedby': undefined })}
                className="flex max-h-[calc(100dvh-2rem)] flex-col gap-0 overflow-hidden border-0 p-0 sm:max-w-2xl [&>button]:flex [&>button]:size-11 [&>button]:items-center [&>button]:justify-center"
            >
                <DialogHeader className="shrink-0 px-5 pt-6 pr-16 text-left sm:px-6 sm:pr-16">
                    <DialogTitle>
                        {mode === 'create'
                            ? t('health_entries.dialog.add_title')
                            : t('health_entries.dialog.edit_title')}
                    </DialogTitle>
                    {mode === 'edit' && (
                        <DialogDescription>
                            {t('health_entries.dialog.edit_description')}
                        </DialogDescription>
                    )}
                </DialogHeader>
                <HealthEntryForm
                    key={`${mode}-${logEntry?.id ?? 'new'}-${type ?? ''}-${defaultMeasuredAt ?? ''}`}
                    mode={mode}
                    type={type}
                    defaultMeasuredAt={defaultMeasuredAt}
                    glucoseReadingTypes={glucoseReadingTypes}
                    insulinTypes={insulinTypes}
                    glucoseUnit={glucoseUnit}
                    recentMedications={recentMedications}
                    recentInsulins={recentInsulins}
                    todaysMeals={todaysMeals}
                    entry={logEntry}
                    onCancel={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}
