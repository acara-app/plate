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
import EditDiabetesLogForm from './edit-form';
import CreateDiabetesLogForm from './form';

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
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create'
                            ? 'Add Diabetes Log Entry'
                            : 'Edit Diabetes Log Entry'}
                    </DialogTitle>
                    <DialogDescription>
                        {mode === 'create'
                            ? 'Record a new entry. Use the tabs to log different types of data.'
                            : 'Update your diabetes log entry.'}
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
