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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { convertGlucoseValue } from '@/lib/utils';
import {
    GlucoseUnit,
    type GlucoseUnitType,
    HealthEntry,
    LogType,
    LogTypeValue,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface EntryFieldsProps {
    type: LogTypeValue;
    entry?: HealthEntry;
    errors: Record<string, string>;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications: RecentMedication[];
    recentInsulins: RecentInsulin[];
    todaysMeals: TodaysMeal[];
}

interface FieldErrors {
    errors: Record<string, string>;
}

function GlucoseFields({
    entry,
    errors,
    glucoseReadingTypes,
    glucoseUnit,
}: FieldErrors & {
    entry?: HealthEntry;
    glucoseReadingTypes: ReadingType[];
    glucoseUnit: string;
}) {
    const { t } = useTranslation('common');
    const [readingType, setReadingType] = useState(
        entry?.glucose_reading_type ?? '',
    );

    const glucosePlaceholder =
        glucoseUnit === GlucoseUnit.MmolL
            ? t('health_entries.glucose.placeholder_mmol')
            : t('health_entries.glucose.placeholder_mgdl');

    const displayGlucoseValue =
        entry?.glucose_value != null
            ? convertGlucoseValue(
                  entry.glucose_value,
                  glucoseUnit as GlucoseUnitType,
              )
            : '';

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="glucose_value">
                    {t('health_entries.glucose.label', { unit: glucoseUnit })}
                </Label>
                <Input
                    id="glucose_value"
                    type="number"
                    name="glucose_value"
                    step="0.1"
                    placeholder={glucosePlaceholder}
                    defaultValue={displayGlucoseValue}
                />
                <InputError message={errors.glucose_value} />
            </div>

            <div className="space-y-2">
                <Label>{t('health_entries.glucose.reading_context')}</Label>
                <input
                    type="hidden"
                    name="glucose_reading_type"
                    value={readingType}
                />
                <ToggleGroup
                    type="single"
                    value={readingType}
                    onValueChange={(value) => value && setReadingType(value)}
                    className="flex flex-wrap justify-start gap-2"
                >
                    {glucoseReadingTypes.map((readingTypeOption) => (
                        <ToggleGroupItem
                            key={readingTypeOption.value}
                            value={readingTypeOption.value}
                            variant="outline"
                            className="capitalize"
                        >
                            {readingTypeOption.label.replace('-', ' ')}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
                <InputError message={errors.glucose_reading_type} />
            </div>
        </div>
    );
}

function FoodFields({
    entry,
    errors,
    todaysMeals,
}: FieldErrors & { entry?: HealthEntry; todaysMeals: TodaysMeal[] }) {
    const { t } = useTranslation('common');
    const [carbsGrams, setCarbsGrams] = useState(
        entry?.carbs_grams != null ? String(entry.carbs_grams) : '',
    );

    return (
        <div className="space-y-4">
            {todaysMeals.length > 0 && (
                <div className="space-y-2">
                    <Label className="text-xs text-muted-foreground">
                        {t('health_entries.food.import_from_plan')}
                    </Label>
                    <div className="flex flex-wrap gap-2">
                        {todaysMeals.map((meal) => (
                            <Button
                                key={meal.id}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setCarbsGrams(String(meal.carbs))
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
                    placeholder={t('health_entries.food.carbs_placeholder')}
                    value={carbsGrams}
                    onChange={(e) => setCarbsGrams(e.target.value)}
                />
                <InputError message={errors.carbs_grams} />
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                    <Label htmlFor="protein_grams">
                        {t('health_entries.food.protein_label', 'Protein (g)')}
                    </Label>
                    <Input
                        id="protein_grams"
                        type="number"
                        name="protein_grams"
                        placeholder={t(
                            'health_entries.food.protein_placeholder',
                            'e.g., 25',
                        )}
                        defaultValue={entry?.protein_grams ?? ''}
                    />
                    <InputError message={errors.protein_grams} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="fat_grams">
                        {t('health_entries.food.fat_label', 'Fat (g)')}
                    </Label>
                    <Input
                        id="fat_grams"
                        type="number"
                        name="fat_grams"
                        placeholder={t(
                            'health_entries.food.fat_placeholder',
                            'e.g., 15',
                        )}
                        defaultValue={entry?.fat_grams ?? ''}
                    />
                    <InputError message={errors.fat_grams} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="calories">
                        {t('health_entries.food.calories_label', 'Calories')}
                    </Label>
                    <Input
                        id="calories"
                        type="number"
                        name="calories"
                        placeholder={t(
                            'health_entries.food.calories_placeholder',
                            'e.g., 400',
                        )}
                        defaultValue={entry?.calories ?? ''}
                    />
                    <InputError message={errors.calories} />
                </div>
            </div>
        </div>
    );
}

function InsulinFields({
    entry,
    errors,
    insulinTypes,
    recentInsulins,
}: FieldErrors & {
    entry?: HealthEntry;
    insulinTypes: ReadingType[];
    recentInsulins: RecentInsulin[];
}) {
    const { t } = useTranslation('common');
    const [insulinUnits, setInsulinUnits] = useState(
        entry?.insulin_units != null ? String(entry.insulin_units) : '',
    );
    const [insulinType, setInsulinType] = useState(entry?.insulin_type ?? '');

    return (
        <div className="space-y-4">
            {recentInsulins.length > 0 && (
                <div className="space-y-2">
                    <Label className="text-xs text-muted-foreground">
                        {t('health_entries.insulin.quick_add')}
                    </Label>
                    <div className="flex flex-wrap gap-2">
                        {recentInsulins.map((ins) => (
                            <Button
                                key={ins.label}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setInsulinUnits(String(ins.units));
                                    setInsulinType(ins.type);
                                }}
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
                        onChange={(e) => setInsulinUnits(e.target.value)}
                    />
                    <InputError message={errors.insulin_units} />
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
                            {insulinTypes.map((insulinTypeOption) => (
                                <SelectItem
                                    key={insulinTypeOption.value}
                                    value={insulinTypeOption.value}
                                >
                                    {insulinTypeOption.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.insulin_type} />
                </div>
            </div>
        </div>
    );
}

function MedicationFields({
    entry,
    errors,
    recentMedications,
}: FieldErrors & {
    entry?: HealthEntry;
    recentMedications: RecentMedication[];
}) {
    const { t } = useTranslation('common');
    const [medicationName, setMedicationName] = useState(
        entry?.medication_name ?? '',
    );
    const [medicationDosage, setMedicationDosage] = useState(
        entry?.medication_dosage ?? '',
    );

    return (
        <div className="space-y-4">
            {recentMedications.length > 0 && (
                <div className="space-y-2">
                    <Label className="text-xs text-muted-foreground">
                        {t('health_entries.medication.quick_add')}
                    </Label>
                    <div className="flex flex-wrap gap-2">
                        {recentMedications.map((med) => (
                            <Button
                                key={med.label}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setMedicationName(med.name);
                                    setMedicationDosage(med.dosage);
                                }}
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
                        {t('health_entries.medication.name_label')}
                    </Label>
                    <Input
                        id="medication_name"
                        type="text"
                        name="medication_name"
                        placeholder={t(
                            'health_entries.medication.name_placeholder',
                        )}
                        value={medicationName}
                        onChange={(e) => setMedicationName(e.target.value)}
                    />
                    <InputError message={errors.medication_name} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="medication_dosage">
                        {t('health_entries.medication.dosage_label')}
                    </Label>
                    <Input
                        id="medication_dosage"
                        type="text"
                        name="medication_dosage"
                        placeholder={t(
                            'health_entries.medication.dosage_placeholder',
                        )}
                        value={medicationDosage}
                        onChange={(e) => setMedicationDosage(e.target.value)}
                    />
                    <InputError message={errors.medication_dosage} />
                </div>
            </div>
        </div>
    );
}

function VitalsFields({
    entry,
    errors,
}: FieldErrors & { entry?: HealthEntry }) {
    const { t } = useTranslation('common');

    return (
        <div className="space-y-4">
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
                        defaultValue={entry?.weight ?? ''}
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
                        placeholder={t('health_entries.vitals.a1c_placeholder')}
                        defaultValue={entry?.a1c_value ?? ''}
                    />
                    <InputError message={errors.a1c_value} />
                </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="blood_pressure_systolic">
                        {t('health_entries.vitals.systolic_label')}
                    </Label>
                    <Input
                        id="blood_pressure_systolic"
                        type="number"
                        name="blood_pressure_systolic"
                        placeholder={t(
                            'health_entries.vitals.systolic_placeholder',
                        )}
                        defaultValue={entry?.blood_pressure_systolic ?? ''}
                    />
                    <InputError message={errors.blood_pressure_systolic} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="blood_pressure_diastolic">
                        {t('health_entries.vitals.diastolic_label')}
                    </Label>
                    <Input
                        id="blood_pressure_diastolic"
                        type="number"
                        name="blood_pressure_diastolic"
                        placeholder={t(
                            'health_entries.vitals.diastolic_placeholder',
                        )}
                        defaultValue={entry?.blood_pressure_diastolic ?? ''}
                    />
                    <InputError message={errors.blood_pressure_diastolic} />
                </div>
            </div>
        </div>
    );
}

function ExerciseFields({
    entry,
    errors,
}: FieldErrors & { entry?: HealthEntry }) {
    const { t } = useTranslation('common');

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
                <Label htmlFor="exercise_type">
                    {t('health_entries.exercise.type_label')}
                </Label>
                <Input
                    id="exercise_type"
                    type="text"
                    name="exercise_type"
                    placeholder={t('health_entries.exercise.type_placeholder')}
                    defaultValue={entry?.exercise_type ?? ''}
                />
                <InputError message={errors.exercise_type} />
            </div>
            <div className="space-y-2">
                <Label htmlFor="exercise_duration_minutes">
                    {t('health_entries.exercise.duration_label')}
                </Label>
                <Input
                    id="exercise_duration_minutes"
                    type="number"
                    name="exercise_duration_minutes"
                    placeholder={t(
                        'health_entries.exercise.duration_placeholder',
                    )}
                    defaultValue={entry?.exercise_duration_minutes ?? ''}
                />
                <InputError message={errors.exercise_duration_minutes} />
            </div>
        </div>
    );
}

export default function EntryFields({
    type,
    entry,
    errors,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: EntryFieldsProps) {
    switch (type) {
        case LogType.Glucose:
            return (
                <GlucoseFields
                    entry={entry}
                    errors={errors}
                    glucoseReadingTypes={glucoseReadingTypes}
                    glucoseUnit={glucoseUnit}
                />
            );
        case LogType.Food:
            return (
                <FoodFields
                    entry={entry}
                    errors={errors}
                    todaysMeals={todaysMeals}
                />
            );
        case LogType.Insulin:
            return (
                <InsulinFields
                    entry={entry}
                    errors={errors}
                    insulinTypes={insulinTypes}
                    recentInsulins={recentInsulins}
                />
            );
        case LogType.Meds:
            return (
                <MedicationFields
                    entry={entry}
                    errors={errors}
                    recentMedications={recentMedications}
                />
            );
        case LogType.Vitals:
            return <VitalsFields entry={entry} errors={errors} />;
        case LogType.Exercise:
            return <ExerciseFields entry={entry} errors={errors} />;
        default:
            return null;
    }
}
