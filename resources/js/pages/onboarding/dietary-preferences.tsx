import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { DietaryPreference, Profile } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface SeverityOption {
    value: string;
    label: string;
    description: string;
}

interface PreferenceData {
    severity: string | null;
    notes: string | null;
}

interface Props {
    profile?: Profile;
    selectedPreferences: number[];
    selectedPreferencesData: Record<number, PreferenceData>;
    preferences: {
        pattern?: DietaryPreference[];
        allergy?: DietaryPreference[];
        intolerance?: DietaryPreference[];
        dislike?: DietaryPreference[];
        restriction?: DietaryPreference[];
    };
    severityOptions: SeverityOption[];
}

export default function DietaryPreferences({
    selectedPreferences,
    selectedPreferencesData,
    preferences,
    severityOptions,
}: Props) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();
    const [selectedIds, setSelectedIds] = useState<number[]>(
        selectedPreferences || [],
    );
    const [preferenceData, setPreferenceData] = useState<
        Record<number, PreferenceData>
    >(selectedPreferencesData || {});

    const togglePreference = (id: number) => {
        if (selectedIds.includes(id)) {
            setSelectedIds((current) => current.filter((p) => p !== id));
            const newData = { ...preferenceData };
            delete newData[id];
            setPreferenceData(newData);
        } else {
            setSelectedIds((current) => [...current, id]);
            setPreferenceData((prev) => ({
                ...prev,
                [id]: { severity: null, notes: null },
            }));
        }
    };

    const updatePreferenceData = (
        id: number,
        field: keyof PreferenceData,
        value: string | null,
    ) => {
        setPreferenceData((prev) => ({
            ...prev,
            [id]: { ...prev[id], [field]: value },
        }));
    };

    const renderPreferenceGroup = (
        title: string,
        items: DietaryPreference[] | undefined,
        showSeverity: boolean = false,
    ) => {
        if (!items || items.length === 0) {
            return null;
        }

        return (
            <div>
                <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
                    {title}
                </h3>
                <div className="grid gap-3 sm:grid-cols-2">
                    {items.map((preference) => {
                        const isChecked = selectedIds.includes(preference.id);
                        const data = preferenceData[preference.id];
                        return (
                            <div
                                key={preference.id}
                                className={cn(
                                    'rounded-lg border p-3 transition-colors',
                                    isChecked
                                        ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20'
                                        : 'border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-700/50 dark:hover:bg-gray-700',
                                )}
                            >
                                <label className="flex cursor-pointer items-start">
                                    <Checkbox
                                        checked={isChecked}
                                        onCheckedChange={() =>
                                            togglePreference(preference.id)
                                        }
                                        className="mt-1 dark:border-gray-500"
                                    />
                                    <div className="ml-3 flex-1">
                                        <span className="block font-medium text-gray-900 dark:text-white">
                                            {preference.name}
                                        </span>
                                        <span className="text-sm text-gray-600 dark:text-gray-300">
                                            {preference.description}
                                        </span>
                                    </div>
                                </label>

                                {isChecked && showSeverity && (
                                    <div className="mt-3 space-y-3 border-t pt-3 dark:border-gray-600">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {t(
                                                    'onboarding.dietary_preferences.severity',
                                                )}
                                            </label>
                                            <select
                                                value={data?.severity || ''}
                                                onChange={(e) =>
                                                    updatePreferenceData(
                                                        preference.id,
                                                        'severity',
                                                        e.target.value || null,
                                                    )
                                                }
                                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="">
                                                    {t(
                                                        'onboarding.dietary_preferences.select_severity',
                                                    )}
                                                </option>
                                                {severityOptions.map((opt) => (
                                                    <option
                                                        key={opt.value}
                                                        value={opt.value}
                                                    >
                                                        {opt.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {t(
                                                    'onboarding.dietary_preferences.notes',
                                                )}
                                            </label>
                                            <Textarea
                                                value={data?.notes || ''}
                                                onChange={(e) =>
                                                    updatePreferenceData(
                                                        preference.id,
                                                        'notes',
                                                        e.target.value || null,
                                                    )
                                                }
                                                rows={2}
                                                maxLength={500}
                                                placeholder={t(
                                                    'onboarding.dietary_preferences.notes_placeholder',
                                                )}
                                                className="text-sm"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    };

    return (
        <>
            <Head title={t('onboarding.dietary_preferences.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-4xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 4,
                                    total: 7,
                                })}
                            </span>
                            <span>57%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-[57%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.dietary_preferences.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.dietary_preferences.description')}
                        </p>

                        <Form
                            {...onboarding.dietaryPreferences.store.form()}
                            disableWhileProcessing
                            className="space-y-8"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Hidden inputs for selected IDs, severities, and notes */}
                                    {selectedIds.map((id) => (
                                        <div key={`inputs-${id}`}>
                                            <input
                                                type="hidden"
                                                name="dietary_preference_ids[]"
                                                value={id}
                                            />
                                            <input
                                                type="hidden"
                                                name="severities[]"
                                                value={
                                                    preferenceData[id]
                                                        ?.severity || ''
                                                }
                                            />
                                            <input
                                                type="hidden"
                                                name="notes[]"
                                                value={
                                                    preferenceData[id]?.notes ||
                                                    ''
                                                }
                                            />
                                        </div>
                                    ))}

                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.patterns',
                                        ),
                                        preferences.pattern,
                                    )}
                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.allergies',
                                        ),
                                        preferences.allergy,
                                        true,
                                    )}
                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.intolerances',
                                        ),
                                        preferences.intolerance,
                                    )}
                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.restrictions',
                                        ),
                                        preferences.restriction,
                                    )}
                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.dislikes',
                                        ),
                                        preferences.dislike,
                                    )}

                                    <InputError
                                        message={errors.dietary_preference_ids}
                                    />

                                    {/* Submit Button */}
                                    <div className="flex items-center justify-between gap-4 border-t pt-6 dark:border-gray-700">
                                        {currentUser?.has_meal_plan && (
                                            <Link
                                                href={dashboard.url()}
                                                className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                            >
                                                {t(
                                                    'onboarding.dietary_preferences.exit',
                                                )}
                                            </Link>
                                        )}
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            {t(
                                                'onboarding.dietary_preferences.continue',
                                            )}
                                        </Button>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {t(
                                                'onboarding.dietary_preferences.selected',
                                                { count: selectedIds.length },
                                            )}
                                        </p>
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
