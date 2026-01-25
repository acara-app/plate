import profileHealthConditions from '@/routes/profile/health-conditions';
import { HealthCondition, Profile } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { GlucoseUnit, type GlucoseUnitType } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';

interface GlucoseUnitOption {
    value: string;
    label: string;
}

interface Props {
    profile?: Profile;
    selectedConditions: number[];
    healthConditions: HealthCondition[];
    glucoseUnitOptions: GlucoseUnitOption[];
    selectedGlucoseUnit?: string | null;
}

export default function HealthConditions({
    selectedConditions,
    healthConditions,
    glucoseUnitOptions,
    selectedGlucoseUnit,
}: Props) {
    const { t } = useTranslation('common');
    const [selectedIds, setSelectedIds] = useState<number[]>(
        selectedConditions || [],
    );
    const [notes, setNotes] = useState<Record<number, string>>({});
    const [expandedId, setExpandedId] = useState<number | null>(null);
    const [glucoseUnit, setGlucoseUnit] = useState<GlucoseUnitType>(
        (selectedGlucoseUnit as GlucoseUnitType) || GlucoseUnit.MmolL,
    );

    const toggleCondition = (id: number) => {
        if (selectedIds.includes(id)) {
            const newNotes = { ...notes };
            delete newNotes[id];
            setNotes(newNotes);
            setSelectedIds((current) => current.filter((c) => c !== id));
        } else {
            setSelectedIds((current) => [...current, id]);
        }
    };

    const updateNote = (id: number, value: string) => {
        setNotes((prev) => ({ ...prev, [id]: value }));
    };

    return (
        <AppLayout>
            <Head title={t('onboarding.health_conditions.title')} />
            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {t('onboarding.health_conditions.heading')}
                </h1>
                <p className="mb-6 text-gray-600 dark:text-gray-300">
                    {t('onboarding.health_conditions.description')}
                </p>

                <Form
                    {...profileHealthConditions.store.form()}
                    disableWhileProcessing
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            {selectedIds.map((id) => (
                                <input
                                    key={`id-${id}`}
                                    type="hidden"
                                    name="health_condition_ids[]"
                                    value={id}
                                />
                            ))}

                            {selectedIds.map((id) => (
                                <input
                                    key={`note-${id}`}
                                    type="hidden"
                                    name="notes[]"
                                    value={notes[id] || ''}
                                />
                            ))}

                            <div className="space-y-3">
                                {healthConditions.map((condition) => {
                                    const isSelected = selectedIds.includes(
                                        condition.id,
                                    );
                                    return (
                                        <div
                                            key={condition.id}
                                            className={cn(
                                                'rounded-lg border p-4 transition-colors',
                                                isSelected
                                                    ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20'
                                                    : 'border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-700/50 dark:hover:bg-gray-700',
                                            )}
                                        >
                                            <label className="flex cursor-pointer items-start">
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={() =>
                                                        toggleCondition(
                                                            condition.id,
                                                        )
                                                    }
                                                    className="mt-1 dark:border-gray-500"
                                                />
                                                <div className="ml-3 flex-1">
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-medium text-gray-900 dark:text-white">
                                                            {condition.name}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                setExpandedId(
                                                                    expandedId ===
                                                                        condition.id
                                                                        ? null
                                                                        : condition.id,
                                                                );
                                                            }}
                                                            className="text-sm text-primary hover:text-primary/80"
                                                        >
                                                            {expandedId ===
                                                            condition.id
                                                                ? t(
                                                                      'onboarding.health_conditions.hide',
                                                                  )
                                                                : t(
                                                                      'onboarding.health_conditions.info',
                                                                  )}
                                                        </button>
                                                    </div>
                                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                                        {condition.description}
                                                    </p>

                                                    {expandedId ===
                                                        condition.id && (
                                                        <div className="mt-2 rounded-md bg-primary/10 p-3 text-sm text-gray-700 dark:bg-primary/20 dark:text-gray-300">
                                                            <p className="font-medium">
                                                                {t(
                                                                    'onboarding.health_conditions.nutritional_impact',
                                                                )}
                                                            </p>
                                                            <p className="mt-1">
                                                                {
                                                                    condition.nutritional_impact
                                                                }
                                                            </p>
                                                        </div>
                                                    )}

                                                    {isSelected && (
                                                        <div className="mt-3">
                                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {t(
                                                                    'onboarding.health_conditions.additional_notes',
                                                                )}
                                                            </label>
                                                            <Textarea
                                                                value={
                                                                    notes[
                                                                        condition
                                                                            .id
                                                                    ] || ''
                                                                }
                                                                onChange={(e) =>
                                                                    updateNote(
                                                                        condition.id,
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                rows={2}
                                                                maxLength={500}
                                                                className="mt-1"
                                                                placeholder={t(
                                                                    'onboarding.health_conditions.notes_placeholder',
                                                                )}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </label>
                                        </div>
                                    );
                                })}
                            </div>

                            <InputError message={errors.health_condition_ids} />

                            <div className="mt-6 rounded-lg border border-primary/30 bg-primary/5 p-4 dark:border-primary/50 dark:bg-primary/10">
                                <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">
                                    {t(
                                        'onboarding.health_conditions.glucose_preference_heading',
                                    )}
                                </h3>
                                <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                    {t(
                                        'onboarding.health_conditions.glucose_preference_description',
                                    )}
                                </p>

                                <input
                                    type="hidden"
                                    name="units_preference"
                                    value={glucoseUnit}
                                />

                                <ToggleGroup
                                    type="single"
                                    value={glucoseUnit}
                                    onValueChange={(value) =>
                                        value &&
                                        setGlucoseUnit(value as GlucoseUnitType)
                                    }
                                    className="justify-start gap-2"
                                >
                                    {glucoseUnitOptions.map((option) => (
                                        <ToggleGroupItem
                                            key={option.value}
                                            value={option.value}
                                            variant="outline"
                                            className="px-4"
                                        >
                                            {option.label}
                                        </ToggleGroupItem>
                                    ))}
                                </ToggleGroup>

                                <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    {t(
                                        'onboarding.health_conditions.glucose_preference_hint',
                                    )}
                                </p>
                            </div>

                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {selectedIds.length > 0
                                        ? t(
                                              'onboarding.health_conditions.selected',
                                              {
                                                  count: selectedIds.length,
                                              },
                                          )
                                        : t(
                                              'onboarding.health_conditions.no_conditions',
                                          )}
                                </p>
                            </div>

                            <div className="flex justify-end gap-3 border-t pt-6 dark:border-gray-700">
                                <Button type="submit" disabled={processing}>
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    {t('save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
