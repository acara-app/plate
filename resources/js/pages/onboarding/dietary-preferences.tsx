import onboarding from '@/routes/onboarding';
import { DietaryPreference, Profile } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

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
                                        ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                        : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700',
                                )}
                            >
                                <Checkbox
                                    checked={isChecked}
                                    onCheckedChange={() =>
                                        togglePreference(preference.id)
                                    }
                                    className="mt-1"
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
            <Head title="Dietary Preferences - Step 4 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-4xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 4 of 5</span>
                            <span>80%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-4/5 rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            Dietary preferences
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Select any dietary patterns, allergies,
                            intolerances, or food dislikes
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
                                        'Dietary Patterns',
                                        preferences.pattern,
                                    )}
                                    {renderPreferenceGroup(
                                        'Allergies',
                                        preferences.allergy,
                                    )}
                                    {renderPreferenceGroup(
                                        'Intolerances',
                                        preferences.intolerance,
                                    )}
                                    {renderPreferenceGroup(
                                        'Food Dislikes',
                                        preferences.dislike,
                                    )}

                                    <InputError
                                        message={errors.dietary_preference_ids}
                                    />

                                    {/* Submit Button */}
                                    <div className="flex items-center justify-between border-t pt-6 dark:border-gray-700">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {selectedIds.length} preference
                                            {selectedIds.length !== 1
                                                ? 's'
                                                : ''}{' '}
                                            selected
                                        </p>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            Continue to Health Conditions
                                        </Button>
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
