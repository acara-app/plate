import StoreDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/StoreDiabetesLogController';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { GlucoseUnit } from '@/types/diabetes';
import { Form } from '@inertiajs/react';
import {
    Activity,
    Droplet,
    HeartPulse,
    Pill,
    Syringe,
    Utensils,
} from 'lucide-react';
import { useState } from 'react';

interface ReadingType {
    value: string;
    label: string;
}

interface RecentMedication {
    name: string;
    dosage: string;
    label: string;
}

interface RecentInsulin {
    units: number;
    type: string;
    label: string;
}

interface TodaysMeal {
    id: number;
    name: string;
    type: string;
    carbs: number;
    label: string;
}

interface CreateDiabetesLogFormProps {
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
    onCancel: () => void;
}

export default function CreateDiabetesLogForm({
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications = [],
    recentInsulins = [],
    todaysMeals = [],
    onCancel,
}: CreateDiabetesLogFormProps) {
    const defaultMeasuredAt = new Date().toISOString().slice(0, 16);
    const [readingType, setReadingType] = useState<string>('');
    const [medicationName, setMedicationName] = useState('');
    const [medicationDosage, setMedicationDosage] = useState('');
    const [insulinUnits, setInsulinUnits] = useState('');
    const [insulinType, setInsulinType] = useState('');
    const [carbsGrams, setCarbsGrams] = useState('');

    const glucosePlaceholder =
        glucoseUnit === GlucoseUnit.MmolL ? 'e.g., 6.7' : 'e.g., 120';

    const handleMedicationChipClick = (med: RecentMedication) => {
        setMedicationName(med.name);
        setMedicationDosage(med.dosage);
    };

    const handleInsulinChipClick = (ins: RecentInsulin) => {
        setInsulinUnits(String(ins.units));
        setInsulinType(ins.type);
    };

    return (
        <Form
            {...StoreDiabetesLogController.form()}
            disableWhileProcessing
            onSuccess={onCancel}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <Tabs defaultValue="glucose" className="w-full">
                        <TabsList className="grid w-full grid-cols-6">
                            <TabsTrigger
                                value="glucose"
                                className="flex items-center gap-1"
                            >
                                <Droplet className="size-3.5" />
                                <span className="hidden sm:inline">
                                    Glucose
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="food"
                                className="flex items-center gap-1"
                            >
                                <Utensils className="size-3.5" />
                                <span className="hidden sm:inline">Food</span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="insulin"
                                className="flex items-center gap-1"
                            >
                                <Syringe className="size-3.5" />
                                <span className="hidden sm:inline">
                                    Insulin
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="meds"
                                className="flex items-center gap-1"
                            >
                                <Pill className="size-3.5" />
                                <span className="hidden sm:inline">Meds</span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="vitals"
                                className="flex items-center gap-1"
                            >
                                <HeartPulse className="size-3.5" />
                                <span className="hidden sm:inline">Vitals</span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="exercise"
                                className="flex items-center gap-1"
                            >
                                <Activity className="size-3.5" />
                                <span className="hidden sm:inline">
                                    Exercise
                                </span>
                            </TabsTrigger>
                        </TabsList>

                        {/* Glucose Tab */}
                        <TabsContent value="glucose" className="space-y-4 pt-4">
                            <div className="space-y-2">
                                <Label htmlFor="glucose_value">
                                    Glucose ({glucoseUnit})
                                </Label>
                                <Input
                                    id="glucose_value"
                                    type="number"
                                    name="glucose_value"
                                    step="0.1"
                                    placeholder={glucosePlaceholder}
                                />
                                <InputError message={errors.glucose_value} />
                            </div>

                            <div className="space-y-2">
                                <Label>Reading Context</Label>
                                <input
                                    type="hidden"
                                    name="glucose_reading_type"
                                    value={readingType}
                                />
                                <ToggleGroup
                                    type="single"
                                    value={readingType}
                                    onValueChange={(value) =>
                                        value && setReadingType(value)
                                    }
                                    className="flex flex-wrap justify-start gap-2"
                                >
                                    {glucoseReadingTypes.map((type) => (
                                        <ToggleGroupItem
                                            key={type.value}
                                            value={type.value}
                                            variant="outline"
                                            className="capitalize"
                                        >
                                            {type.label.replace('-', ' ')}
                                        </ToggleGroupItem>
                                    ))}
                                </ToggleGroup>
                                <InputError
                                    message={errors.glucose_reading_type}
                                />
                            </div>
                        </TabsContent>

                        {/* Food Tab */}
                        <TabsContent value="food" className="space-y-4 pt-4">
                            {todaysMeals.length > 0 && (
                                <div className="space-y-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Import from Today's Plan
                                    </Label>
                                    <div className="flex flex-wrap gap-2">
                                        {todaysMeals.map((meal) => (
                                            <Button
                                                key={meal.id}
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setCarbsGrams(
                                                        String(meal.carbs),
                                                    )
                                                }
                                            >
                                                🍽️ {meal.label}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="carbs_grams">
                                    Carbohydrates (grams)
                                </Label>
                                <Input
                                    id="carbs_grams"
                                    type="number"
                                    name="carbs_grams"
                                    placeholder="e.g., 45"
                                    value={carbsGrams}
                                    onChange={(e) =>
                                        setCarbsGrams(e.target.value)
                                    }
                                />
                                <InputError message={errors.carbs_grams} />
                            </div>
                        </TabsContent>

                        {/* Insulin Tab */}
                        <TabsContent value="insulin" className="space-y-4 pt-4">
                            {recentInsulins.length > 0 && (
                                <div className="space-y-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Quick Add
                                    </Label>
                                    <div className="flex flex-wrap gap-2">
                                        {recentInsulins.map((ins) => (
                                            <Button
                                                key={ins.label}
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleInsulinChipClick(ins)
                                                }
                                            >
                                                + {ins.label}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="insulin_units">Units</Label>
                                    <Input
                                        id="insulin_units"
                                        type="number"
                                        name="insulin_units"
                                        step="0.5"
                                        placeholder="e.g., 10"
                                        value={insulinUnits}
                                        onChange={(e) =>
                                            setInsulinUnits(e.target.value)
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
                                        value={insulinType}
                                        onValueChange={setInsulinType}
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
                        </TabsContent>

                        {/* Medication Tab */}
                        <TabsContent value="meds" className="space-y-4 pt-4">
                            {recentMedications.length > 0 && (
                                <div className="space-y-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Quick Add
                                    </Label>
                                    <div className="flex flex-wrap gap-2">
                                        {recentMedications.map((med) => (
                                            <Button
                                                key={med.label}
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleMedicationChipClick(
                                                        med,
                                                    )
                                                }
                                            >
                                                + {med.label}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            )}

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
                                        value={medicationName}
                                        onChange={(e) =>
                                            setMedicationName(e.target.value)
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
                                        placeholder="e.g., 500mg"
                                        value={medicationDosage}
                                        onChange={(e) =>
                                            setMedicationDosage(e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={errors.medication_dosage}
                                    />
                                </div>
                            </div>
                        </TabsContent>

                        {/* Vitals Tab */}
                        <TabsContent value="vitals" className="space-y-4 pt-4">
                            <div className="grid gap-4 md:grid-cols-2">
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
                            <div className="grid gap-4 md:grid-cols-2">
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
                            </div>
                        </TabsContent>

                        {/* Exercise Tab */}
                        <TabsContent
                            value="exercise"
                            className="space-y-4 pt-4"
                        >
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
                        </TabsContent>
                    </Tabs>

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

                    {/* Date & Time and Actions */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="space-y-2 sm:flex-1">
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
                    </div>
                </>
            )}
        </Form>
    );
}
