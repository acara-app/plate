import GlucoseReadingController from '@/actions/App/Http/Controllers/GlucoseReadingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Form } from '@inertiajs/react';

interface ReadingType {
    value: string;
    label: string;
}

interface CreateGlucoseReadingFormProps {
    readingTypes: ReadingType[];
    onCancel: () => void;
}

export default function CreateGlucoseReadingForm({
    readingTypes,
    onCancel,
}: CreateGlucoseReadingFormProps) {
    const defaultMeasuredAt = new Date().toISOString().slice(0, 16);

    return (
        <Form
            {...GlucoseReadingController.store.form()}
            disableWhileProcessing
            onSuccess={onCancel}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="reading_value">
                                Reading Value (mg/dL)
                            </Label>
                            <Input
                                id="reading_value"
                                type="number"
                                name="reading_value"
                                step="0.1"
                                placeholder="e.g., 120.5"
                                required
                            />
                            <InputError message={errors.reading_value} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="reading_type">Reading Type</Label>
                            <Select name="reading_type" required>
                                <SelectTrigger id="reading_type">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {readingTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.reading_type} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="measured_at">
                                Measurement Date & Time
                            </Label>
                            <Input
                                id="measured_at"
                                type="datetime-local"
                                name="measured_at"
                                defaultValue={defaultMeasuredAt}
                                required
                            />
                            <InputError message={errors.measured_at} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes (Optional)</Label>
                        <Textarea
                            id="notes"
                            name="notes"
                            placeholder="Any additional notes about this reading..."
                            maxLength={500}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onCancel}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Create
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
