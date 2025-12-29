import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import EditDiabetesLogForm from './edit-form';
import CreateDiabetesLogForm from './form';

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

interface DialogProps {
    mode: 'create' | 'edit';
    open: boolean;
    onOpenChange: (open: boolean) => void;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    logEntry?: DiabetesLogEntry;
}

export default function DiabetesLogDialog({
    mode,
    open,
    onOpenChange,
    glucoseReadingTypes,
    insulinTypes,
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
                            ? 'Record a new diabetes log entry with any combination of glucose, insulin, medications, vitals, or exercise.'
                            : 'Update your diabetes log entry.'}
                    </DialogDescription>
                </DialogHeader>
                {mode === 'create' ? (
                    <CreateDiabetesLogForm
                        glucoseReadingTypes={glucoseReadingTypes}
                        insulinTypes={insulinTypes}
                        onCancel={() => onOpenChange(false)}
                    />
                ) : (
                    logEntry && (
                        <EditDiabetesLogForm
                            glucoseReadingTypes={glucoseReadingTypes}
                            insulinTypes={insulinTypes}
                            logEntry={logEntry}
                            onCancel={() => onOpenChange(false)}
                        />
                    )
                )}
            </DialogContent>
        </Dialog>
    );
}
