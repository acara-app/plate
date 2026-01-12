import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { DietaryPreference, Profile } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface Props {
    profile?: Profile;
    selectedPreferences: number[];
    preferences: {
        pattern?: DietaryPreference[];
        allergy?: DietaryPreference[];
        intolerance?: DietaryPreference[];
        dislike?: DietaryPreference[];
    };
}

export default function DietaryPreferences({
    selectedPreferences,
    preferences,
}: Props) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();
    const [selectedIds, setSelectedIds] = useState<number[]>(
        selectedPreferences || [],
    );

    const togglePreference = (id: number) => {
        setSelectedIds((current) =>
            current.includes(id)
                ? current.filter((p) => p !== id)
                : [...current, id],
        );
    };

    const renderPreferenceGroup = (
        title: string,
        items: DietaryPreference[] | undefined,
    ) => {
        if (!items || items.length === 0) {
            return null;
        }

        return (
            <div>
                <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
                    {title}
                </h3>
                <div className="grid gap-2 sm:grid-cols-2">
                    {items.map((preference) => {
                        const isChecked = selectedIds.includes(preference.id);
                        return (
                            <label
                                key={preference.id}
                                className={cn(
                                    'flex cursor-pointer items-start rounded-lg border p-3 transition-colors',
                                    isChecked
                                        ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20'
                                        : 'border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-700/50 dark:hover:bg-gray-700',
                                )}
                            >
                                <Checkbox
                                    checked={isChecked}
                                    onCheckedChange={() =>
                                        togglePreference(preference.id)
                                    }
                                    className="mt-1 dark:border-gray-500"
                                />
                                <div className="ml-3">
                                    <span className="block font-medium text-gray-900 dark:text-white">
                                        {preference.name}
                                    </span>
                                    <span className="text-sm text-gray-600 dark:text-gray-300">
                                        {preference.description}
                                    </span>
                                </div>
                            </label>
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
                                    total: 6,
                                })}
                            </span>
                            <span>80%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-4/5 overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
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
                                    {/* Hidden inputs for selected IDs */}
                                    {selectedIds.map((id) => (
                                        <input
                                            key={id}
                                            type="hidden"
                                            name="dietary_preference_ids[]"
                                            value={id}
                                        />
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
                                    )}
                                    {renderPreferenceGroup(
                                        t(
                                            'onboarding.dietary_preferences.intolerances',
                                        ),
                                        preferences.intolerance,
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
