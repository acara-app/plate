import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Profile, UserMedication } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import useSharedProps from '@/hooks/use-shared-props';
import { generateUUID } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface MedicationInput {
    id: string;
    name: string;
    dosage: string;
    frequency: string;
    purpose: string;
    started_at: string;
}

interface Props {
    profile?: Profile;
    medications: UserMedication[];
}

export default function Medications({ medications }: Props) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();

    const initialMedications: MedicationInput[] =
        medications.length > 0
            ? medications.map((med) => ({
                  id: generateUUID(),
                  name: med.name,
                  dosage: med.dosage || '',
                  frequency: med.frequency || '',
                  purpose: med.purpose || '',
                  started_at: med.started_at || '',
              }))
            : [];

    const [medicationsList, setMedicationsList] =
        useState<MedicationInput[]>(initialMedications);

    const addMedication = () => {
        setMedicationsList([
            ...medicationsList,
            {
                id: generateUUID(),
                name: '',
                dosage: '',
                frequency: '',
                purpose: '',
                started_at: '',
            },
        ]);
    };

    const removeMedication = (id: string) => {
        setMedicationsList(medicationsList.filter((med) => med.id !== id));
    };

    const updateMedication = (
        id: string,
        field: keyof MedicationInput,
        value: string,
    ) => {
        setMedicationsList(
            medicationsList.map((med) =>
                med.id === id ? { ...med, [field]: value } : med,
            ),
        );
    };

    const frequencyOptions = [
        'Once daily',
        'Twice daily',
        'Three times daily',
        'Four times daily',
        'As needed',
        'With meals',
        'Before meals',
        'At bedtime',
        'Weekly',
        'Other',
    ];

    return (
        <>
            <Head title={t('onboarding.medications.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-4xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 6,
                                    total: 7,
                                })}
                            </span>
                            <span>86%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-[86%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.medications.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.medications.description')}
                        </p>

                        <Form
                            {...onboarding.medications.store.form()}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Hidden inputs for medications */}
                                    {medicationsList.map((med, index) => (
                                        <div key={`hidden-${med.id}`}>
                                            <input
                                                type="hidden"
                                                name={`medications[${index}][name]`}
                                                value={med.name}
                                            />
                                            <input
                                                type="hidden"
                                                name={`medications[${index}][dosage]`}
                                                value={med.dosage}
                                            />
                                            <input
                                                type="hidden"
                                                name={`medications[${index}][frequency]`}
                                                value={med.frequency}
                                            />
                                            <input
                                                type="hidden"
                                                name={`medications[${index}][purpose]`}
                                                value={med.purpose}
                                            />
                                            <input
                                                type="hidden"
                                                name={`medications[${index}][started_at]`}
                                                value={med.started_at}
                                            />
                                        </div>
                                    ))}

                                    {medicationsList.length === 0 ? (
                                        <div className="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-600">
                                            <p className="mb-4 text-gray-500 dark:text-gray-400">
                                                {t(
                                                    'onboarding.medications.no_medications',
                                                )}
                                            </p>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={addMedication}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                {t(
                                                    'onboarding.medications.add_medication',
                                                )}
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {medicationsList.map(
                                                (med, index) => (
                                                    <div
                                                        key={med.id}
                                                        className="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                                    >
                                                        <div className="mb-4 flex items-center justify-between">
                                                            <h3 className="font-medium text-gray-900 dark:text-white">
                                                                {t(
                                                                    'onboarding.medications.medication_number',
                                                                    {
                                                                        number:
                                                                            index +
                                                                            1,
                                                                    },
                                                                )}
                                                            </h3>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    removeMedication(
                                                                        med.id,
                                                                    )
                                                                }
                                                                className="text-red-500 hover:text-red-700"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>

                                                        <div className="grid gap-4 sm:grid-cols-2">
                                                            <div>
                                                                <Label
                                                                    htmlFor={`name-${med.id}`}
                                                                >
                                                                    {t(
                                                                        'onboarding.medications.name',
                                                                    )}{' '}
                                                                    *
                                                                </Label>
                                                                <Input
                                                                    id={`name-${med.id}`}
                                                                    value={
                                                                        med.name
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        updateMedication(
                                                                            med.id,
                                                                            'name',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    placeholder={t(
                                                                        'onboarding.medications.name_placeholder',
                                                                    )}
                                                                    className="mt-1"
                                                                />
                                                            </div>

                                                            <div>
                                                                <Label
                                                                    htmlFor={`dosage-${med.id}`}
                                                                >
                                                                    {t(
                                                                        'onboarding.medications.dosage',
                                                                    )}
                                                                </Label>
                                                                <Input
                                                                    id={`dosage-${med.id}`}
                                                                    value={
                                                                        med.dosage
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        updateMedication(
                                                                            med.id,
                                                                            'dosage',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    placeholder={t(
                                                                        'onboarding.medications.dosage_placeholder',
                                                                    )}
                                                                    className="mt-1"
                                                                />
                                                            </div>

                                                            <div>
                                                                <Label
                                                                    htmlFor={`frequency-${med.id}`}
                                                                >
                                                                    {t(
                                                                        'onboarding.medications.frequency',
                                                                    )}
                                                                </Label>
                                                                <select
                                                                    id={`frequency-${med.id}`}
                                                                    value={
                                                                        med.frequency
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        updateMedication(
                                                                            med.id,
                                                                            'frequency',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                >
                                                                    <option value="">
                                                                        {t(
                                                                            'onboarding.medications.select_frequency',
                                                                        )}
                                                                    </option>
                                                                    {frequencyOptions.map(
                                                                        (
                                                                            freq,
                                                                        ) => (
                                                                            <option
                                                                                key={
                                                                                    freq
                                                                                }
                                                                                value={
                                                                                    freq
                                                                                }
                                                                            >
                                                                                {
                                                                                    freq
                                                                                }
                                                                            </option>
                                                                        ),
                                                                    )}
                                                                </select>
                                                            </div>

                                                            <div>
                                                                <Label
                                                                    htmlFor={`purpose-${med.id}`}
                                                                >
                                                                    {t(
                                                                        'onboarding.medications.purpose',
                                                                    )}
                                                                </Label>
                                                                <Input
                                                                    id={`purpose-${med.id}`}
                                                                    value={
                                                                        med.purpose
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        updateMedication(
                                                                            med.id,
                                                                            'purpose',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    placeholder={t(
                                                                        'onboarding.medications.purpose_placeholder',
                                                                    )}
                                                                    className="mt-1"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ),
                                            )}

                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={addMedication}
                                                className="w-full"
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                {t(
                                                    'onboarding.medications.add_another',
                                                )}
                                            </Button>
                                        </div>
                                    )}

                                    <InputError message={errors.medications} />

                                    {/* Info Box */}
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                        <p className="text-sm text-blue-800 dark:text-blue-200">
                                            {t('onboarding.medications.info')}
                                        </p>
                                    </div>

                                    {/* Footer Section */}
                                    <div className="flex flex-col items-center gap-4 border-t pt-6 dark:border-gray-700">
                                        {/* Action Buttons Row */}
                                        <div className="flex justify-center gap-3">
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                                className="min-w-[100px]"
                                            >
                                                {t(
                                                    'onboarding.medications.skip',
                                                )}
                                            </Button>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="min-w-[120px]"
                                            >
                                                {processing && (
                                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                                )}
                                                {t(
                                                    'onboarding.medications.continue',
                                                )}
                                            </Button>
                                        </div>

                                        {/* Exit Link - Centered Below */}
                                        {currentUser?.has_meal_plan && (
                                            <Link
                                                href={dashboard.url()}
                                                className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                            >
                                                {t(
                                                    'onboarding.medications.exit',
                                                )}
                                            </Link>
                                        )}
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
