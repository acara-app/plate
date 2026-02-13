import StoreHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/StoreHealthEntryController';
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
import { formatLocalDatetime } from '@/lib/format-local-datetime';
import {
    GlucoseUnit,
    LogType,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
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
import { useTranslation } from 'react-i18next';

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
    const { t } = useTranslation('common');
    const defaultMeasuredAt = formatLocalDatetime(new Date());
    const [activeTab, setActiveTab] = useState<string>(LogType.Glucose);
    /**
     * Form State for filling inputs via label chips
     */
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
            {...StoreHealthEntryController.form()}
            disableWhileProcessing
            onSuccess={onCancel}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <input type="hidden" name="log_type" value={activeTab} />
                    <Tabs
                        defaultValue={LogType.Glucose}
                        onValueChange={setActiveTab}
                        className="w-full"
                    >
                        <TabsList className="grid w-full grid-cols-6">
                            <TabsTrigger
                                value={LogType.Glucose}
                                className="flex items-center gap-1"
                            >
                                <Droplet className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.glucose')}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value={LogType.Food}
                                className="flex items-center gap-1"
                            >
                                <Utensils className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.food')}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value={LogType.Insulin}
                                className="flex items-center gap-1"
                            >
                                <Syringe className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.insulin')}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value={LogType.Meds}
                                className="flex items-center gap-1"
                            >
                                <Pill className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.meds')}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value={LogType.Vitals}
                                className="flex items-center gap-1"
                            >
                                <HeartPulse className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.vitals')}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger
                                value={LogType.Exercise}
                                className="flex items-center gap-1"
                            >
                                <Activity className="size-3.5" />
                                <span className="hidden sm:inline">
                                    {t('health_entries.tabs.exercise')}
                                </span>
                            </TabsTrigger>
                        </TabsList>

                        {/* Glucose Tab */}
                        <TabsContent
                            value={LogType.Glucose}
                            className="space-y-4 pt-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="glucose_value">
                                    {t('health_entries.glucose.label', {
                                        unit: glucoseUnit,
                                    })}
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
                                <Label>
                                    {t('health_entries.glucose.reading_context')}
                                </Label>
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
                        <TabsContent
                            value={LogType.Food}
                            className="space-y-4 pt-4"
                        >
                            {todaysMeals.length > 0 && (
                                <div className="space-y-2">
                                    <Label className="text-xs text-muted-foreground">
                                        {t(
                                            'health_entries.food.import_from_plan',
                                        )}
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
                                    {t('health_entries.food.carbs_label')}
                                </Label>
                                <Input
                                    id="carbs_grams"
                                    type="number"
                                    name="carbs_grams"
                                    placeholder={t(
                                        'health_entries.food.carbs_placeholder',
                                    )}
                                    value={carbsGrams}
                                    onChange={(e) =>
                                        setCarbsGrams(e.target.value)
                                    }
                                />
                                <InputError message={errors.carbs_grams} />
                            </div>
                        </TabsContent>

                        {/* Insulin Tab */}
                        <TabsContent
                            value={LogType.Insulin}
                            className="space-y-4 pt-4"
                        >
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
                                    <Label htmlFor="insulin_units">
                                        {t('health_entries.insulin.units_label')}
                                    </Label>
                                    <Input
                                        id="insulin_units"
                                        type="number"
                                        name="insulin_units"
                                        step="0.5"
                                        placeholder={t(
                                            'health_entries.insulin.units_placeholder',
                                        )}
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
                                    <Label htmlFor="insulin_type">
                                        {t('health_entries.insulin.type_label')}
                                    </Label>
                                    <Select
                                        name="insulin_type"
                                        value={insulinType}
                                        onValueChange={setInsulinType}
                                    >
                                        <SelectTrigger id="insulin_type">
                                            <SelectValue
                                                placeholder={t(
                                                    'health_entries.insulin.type_placeholder',
                                                )}
                                            />
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
                        <TabsContent
                            value={LogType.Meds}
                            className="space-y-4 pt-4"
                        >
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
                                        {t(
                                            'health_entries.medication.name_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="medication_name"
                                        type="text"
                                        name="medication_name"
                                        placeholder={t(
                                            'health_entries.medication.name_placeholder',
                                        )}
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
                                        {t(
                                            'health_entries.medication.dosage_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="medication_dosage"
                                        type="text"
                                        name="medication_dosage"
                                        placeholder={t(
                                            'health_entries.medication.dosage_placeholder',
                                        )}
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
                        <TabsContent
                            value={LogType.Vitals}
                            className="space-y-4 pt-4"
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="weight">
                                        {t('health_entries.vitals.weight_label')}
                                    </Label>
                                    <Input
                                        id="weight"
                                        type="number"
                                        name="weight"
                                        step="0.1"
                                        placeholder={t(
                                            'health_entries.vitals.weight_placeholder',
                                        )}
                                    />
                                    <InputError message={errors.weight} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="a1c_value">
                                        {t('health_entries.vitals.a1c_label')}
                                    </Label>
                                    <Input
                                        id="a1c_value"
                                        type="number"
                                        name="a1c_value"
                                        step="0.1"
                                        placeholder={t(
                                            'health_entries.vitals.a1c_placeholder',
                                        )}
                                    />
                                    <InputError message={errors.a1c_value} />
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="blood_pressure_systolic">
                                        {t(
                                            'health_entries.vitals.systolic_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="blood_pressure_systolic"
                                        type="number"
                                        name="blood_pressure_systolic"
                                        placeholder={t(
                                            'health_entries.vitals.systolic_placeholder',
                                        )}
                                    />
                                    <InputError
                                        message={errors.blood_pressure_systolic}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="blood_pressure_diastolic">
                                        {t(
                                            'health_entries.vitals.diastolic_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="blood_pressure_diastolic"
                                        type="number"
                                        name="blood_pressure_diastolic"
                                        placeholder={t(
                                            'health_entries.vitals.diastolic_placeholder',
                                        )}
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
                            value={LogType.Exercise}
                            className="space-y-4 pt-4"
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="exercise_type">
                                        {t('health_entries.exercise.type_label')}
                                    </Label>
                                    <Input
                                        id="exercise_type"
                                        type="text"
                                        name="exercise_type"
                                        placeholder={t(
                                            'health_entries.exercise.type_placeholder',
                                        )}
                                    />
                                    <InputError
                                        message={errors.exercise_type}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="exercise_duration_minutes">
                                        {t(
                                            'health_entries.exercise.duration_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="exercise_duration_minutes"
                                        type="number"
                                        name="exercise_duration_minutes"
                                        placeholder={t(
                                            'health_entries.exercise.duration_placeholder',
                                        )}
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
                        <Label htmlFor="notes">
                            {t('health_entries.common.notes_label')}
                        </Label>
                        <Textarea
                            id="notes"
                            name="notes"
                            placeholder={t(
                                'health_entries.common.notes_placeholder',
                            )}
                            maxLength={500}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    {/* Date & Time and Actions */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="space-y-2 sm:flex-1">
                            <Label htmlFor="measured_at">
                                {t('health_entries.common.date_time_label')}
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
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onCancel}
                            >
                                {t('health_entries.common.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {t('health_entries.common.create')}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
