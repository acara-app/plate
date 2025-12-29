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

interface EditDiabetesLogFormProps {
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    logEntry: DiabetesLogEntry;
    onCancel: () => void;
}

export default function EditDiabetesLogForm({
    glucoseReadingTypes,
    insulinTypes,
    logEntry,
    onCancel,
}: EditDiabetesLogFormProps) {
    const measuredAt = new Date(logEntry.measured_at)
        .toISOString()
        .slice(0, 16);
    const [showInsulin, setShowInsulin] = useState(!!logEntry.insulin_units);
    const [showMedication, setShowMedication] = useState(
        !!logEntry.medication_name,
    );
    const [showVitals, setShowVitals] = useState(
        !!logEntry.weight ||
            !!logEntry.blood_pressure_systolic ||
            !!logEntry.a1c_value,
    );
    const [showExercise, setShowExercise] = useState(!!logEntry.exercise_type);

    return (
        <Form
            {...DiabetesLogController.update.form(logEntry.id)}
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
                                defaultValue={logEntry.glucose_value ?? ''}
                            />
                            <InputError message={errors.glucose_value} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="glucose_reading_type">
                                Reading Type
                            </Label>
                            <Select
                                name="glucose_reading_type"
                                defaultValue={
                                    logEntry.glucose_reading_type ?? undefined
                                }
                            >
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
                                defaultValue={measuredAt}
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
                                defaultValue={logEntry.carbs_grams ?? ''}
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
                                        defaultValue={
                                            logEntry.insulin_units ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.insulin_units}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="insulin_type">Type</Label>
                                    <Select
                                        name="insulin_type"
                                        defaultValue={
                                            logEntry.insulin_type ?? undefined
                                        }
                                    >
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
                                        defaultValue={
                                            logEntry.medication_name ?? ''
                                        }
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
                                        defaultValue={
                                            logEntry.medication_dosage ?? ''
                                        }
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
                                        defaultValue={logEntry.weight ?? ''}
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
                                        defaultValue={
                                            logEntry.blood_pressure_systolic ??
                                            ''
                                        }
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
                                        defaultValue={
                                            logEntry.blood_pressure_diastolic ??
                                            ''
                                        }
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
                                        defaultValue={logEntry.a1c_value ?? ''}
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
                                        defaultValue={
                                            logEntry.exercise_type ?? ''
                                        }
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
                                        defaultValue={
                                            logEntry.exercise_duration_minutes ??
                                            ''
                                        }
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
                            defaultValue={logEntry.notes ?? ''}
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
                            Update
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
