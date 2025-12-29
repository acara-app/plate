import DiabetesLogController from '@/actions/App/Http/Controllers/DiabetesLogController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';

interface ReadingType {
    value: string;
    label: string;
}

interface CreateDiabetesLogFormProps {
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    onCancel: () => void;
}

export default function CreateDiabetesLogForm({
    glucoseReadingTypes,
    insulinTypes,
    onCancel,
}: CreateDiabetesLogFormProps) {
    const defaultMeasuredAt = new Date().toISOString().slice(0, 16);
    const [showInsulin, setShowInsulin] = useState(false);
    const [showMedication, setShowMedication] = useState(false);
    const [showVitals, setShowVitals] = useState(false);
    const [showExercise, setShowExercise] = useState(false);

    return (
        <Form
            {...DiabetesLogController.store.form()}
            disableWhileProcessing
            onSuccess={onCancel}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    {/* Primary: Glucose Reading */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="glucose_value">
                                Glucose (mg/dL)
                            </Label>
                            <Input
                                id="glucose_value"
                                type="number"
                                name="glucose_value"
                                step="0.1"
                                placeholder="e.g., 120.5"
                            />
                            <InputError message={errors.glucose_value} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="glucose_reading_type">
                                Reading Type
                            </Label>
                            <Select name="glucose_reading_type">
                                <SelectTrigger id="glucose_reading_type">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {glucoseReadingTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.glucose_reading_type} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="measured_at">Date & Time</Label>
                            <Input
                                id="measured_at"
                                type="datetime-local"
                                name="measured_at"
                                defaultValue={defaultMeasuredAt}
                                required
                            />
                            <InputError message={errors.measured_at} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="carbs_grams">Carbs (grams)</Label>
                            <Input
                                id="carbs_grams"
                                type="number"
                                name="carbs_grams"
                                placeholder="e.g., 45"
                            />
                            <InputError message={errors.carbs_grams} />
                        </div>
                    </div>

                    {/* Insulin Section */}
                    <Collapsible
                        open={showInsulin}
                        onOpenChange={setShowInsulin}
                    >
                        <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium hover:text-primary">
                            <ChevronDown
                                className={`size-4 transition-transform ${showInsulin ? 'rotate-180' : ''}`}
                            />
                            Insulin
                        </CollapsibleTrigger>
                        <CollapsibleContent className="pt-2">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="insulin_units">Units</Label>
                                    <Input
                                        id="insulin_units"
                                        type="number"
                                        name="insulin_units"
                                        step="0.5"
                                        placeholder="e.g., 10"
                                    />
                                    <InputError
                                        message={errors.insulin_units}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="insulin_type">Type</Label>
                                    <Select name="insulin_type">
                                        <SelectTrigger id="insulin_type">
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {insulinTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.insulin_type} />
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>

                    {/* Medication Section */}
                    <Collapsible
                        open={showMedication}
                        onOpenChange={setShowMedication}
                    >
                        <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium hover:text-primary">
                            <ChevronDown
                                className={`size-4 transition-transform ${showMedication ? 'rotate-180' : ''}`}
                            />
                            Medication
                        </CollapsibleTrigger>
                        <CollapsibleContent className="pt-2">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="medication_name">
                                        Medication Name
                                    </Label>
                                    <Input
                                        id="medication_name"
                                        type="text"
                                        name="medication_name"
                                        placeholder="e.g., Metformin"
                                    />
                                    <InputError
                                        message={errors.medication_name}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="medication_dosage">
                                        Dosage
                                    </Label>
                                    <Input
                                        id="medication_dosage"
                                        type="text"
                                        name="medication_dosage"
                                        placeholder="e.g., 500mg"
                                    />
                                    <InputError
                                        message={errors.medication_dosage}
                                    />
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>

                    {/* Vitals Section */}
                    <Collapsible open={showVitals} onOpenChange={setShowVitals}>
                        <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium hover:text-primary">
                            <ChevronDown
                                className={`size-4 transition-transform ${showVitals ? 'rotate-180' : ''}`}
                            />
                            Vitals
                        </CollapsibleTrigger>
                        <CollapsibleContent className="pt-2">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="weight">Weight (lbs)</Label>
                                    <Input
                                        id="weight"
                                        type="number"
                                        name="weight"
                                        step="0.1"
                                        placeholder="e.g., 165"
                                    />
                                    <InputError message={errors.weight} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="blood_pressure_systolic">
                                        Systolic BP
                                    </Label>
                                    <Input
                                        id="blood_pressure_systolic"
                                        type="number"
                                        name="blood_pressure_systolic"
                                        placeholder="e.g., 120"
                                    />
                                    <InputError
                                        message={errors.blood_pressure_systolic}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="blood_pressure_diastolic">
                                        Diastolic BP
                                    </Label>
                                    <Input
                                        id="blood_pressure_diastolic"
                                        type="number"
                                        name="blood_pressure_diastolic"
                                        placeholder="e.g., 80"
                                    />
                                    <InputError
                                        message={
                                            errors.blood_pressure_diastolic
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="a1c_value">A1C (%)</Label>
                                    <Input
                                        id="a1c_value"
                                        type="number"
                                        name="a1c_value"
                                        step="0.1"
                                        placeholder="e.g., 6.5"
                                    />
                                    <InputError message={errors.a1c_value} />
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>

                    {/* Exercise Section */}
                    <Collapsible
                        open={showExercise}
                        onOpenChange={setShowExercise}
                    >
                        <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium hover:text-primary">
                            <ChevronDown
                                className={`size-4 transition-transform ${showExercise ? 'rotate-180' : ''}`}
                            />
                            Exercise
                        </CollapsibleTrigger>
                        <CollapsibleContent className="pt-2">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="exercise_type">
                                        Exercise Type
                                    </Label>
                                    <Input
                                        id="exercise_type"
                                        type="text"
                                        name="exercise_type"
                                        placeholder="e.g., Walking, Running"
                                    />
                                    <InputError
                                        message={errors.exercise_type}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="exercise_duration_minutes">
                                        Duration (minutes)
                                    </Label>
                                    <Input
                                        id="exercise_duration_minutes"
                                        type="number"
                                        name="exercise_duration_minutes"
                                        placeholder="e.g., 30"
                                    />
                                    <InputError
                                        message={
                                            errors.exercise_duration_minutes
                                        }
                                    />
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>

                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes (Optional)</Label>
                        <Textarea
                            id="notes"
                            name="notes"
                            placeholder="Any additional notes..."
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
