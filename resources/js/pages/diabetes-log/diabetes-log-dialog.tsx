import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DiabetesLogEntry,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { useTranslation } from 'react-i18next';
import CreateDiabetesLogForm from './add-form';
import EditDiabetesLogForm from './edit-form';

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
    logEntry?: DiabetesLogEntry;
}

export default function DiabetesLogDialog({
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
}: DialogProps) {
    const { t } = useTranslation('common');
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create'
                            ? t('diabetes_log.dialog.add_title')
                            : t('diabetes_log.dialog.edit_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {mode === 'create'
                            ? t('diabetes_log.dialog.add_description')
                            : t('diabetes_log.dialog.edit_description')}
                    </DialogDescription>
                </DialogHeader>
                {mode === 'create' ? (
                    <CreateDiabetesLogForm
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        glucoseUnit={glucoseUnit}
                        recentMedications={recentMedications}
                        recentInsulins={recentInsulins}
                        todaysMeals={todaysMeals}
                        onCancel={() => onOpenChange(false)}
                    />
                ) : (
                    logEntry && (
                        <EditDiabetesLogForm
                            glucoseReadingTypes={glucoseReadingTypes}
                            insulinTypes={insulinTypes}
                            glucoseUnit={glucoseUnit}
                            logEntry={logEntry}
                            recentMedications={recentMedications}
                            recentInsulins={recentInsulins}
                            todaysMeals={todaysMeals}
                            onCancel={() => onOpenChange(false)}
                        />
                    )
                )}
            </DialogContent>
        </Dialog>
    );
}
