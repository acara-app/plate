import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import EditGlucoseReadingForm from './edit-form';
import CreateGlucoseReadingForm from './form';

interface ReadingType {
    value: string;
    label: string;
}

interface GlucoseReading {
    id: number;
    reading_value: number;
    reading_type: string;
    measured_at: string;
    notes: string | null;
}

interface GlucoseReadingDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    readingTypes: ReadingType[];
    reading?: GlucoseReading | null;
    mode: 'create' | 'edit';
}

export default function GlucoseReadingDialog({
    open,
    onOpenChange,
    readingTypes,
    reading,
    mode,
}: GlucoseReadingDialogProps) {
    const handleClose = () => {
        onOpenChange(false);
    };

    const isEdit = mode === 'edit';
    const title = isEdit ? 'Edit Glucose Reading' : 'Record Glucose Reading';
    const description = isEdit
        ? 'Update your blood glucose measurement details'
        : 'Enter your blood glucose measurement details';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {isEdit && reading ? (
                    <EditGlucoseReadingForm
                        readingTypes={readingTypes}
                        reading={reading}
                        onCancel={handleClose}
                    />
                ) : (
                    <CreateGlucoseReadingForm
                        readingTypes={readingTypes}
                        onCancel={handleClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
