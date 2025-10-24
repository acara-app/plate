import onboarding from '@/routes/onboarding';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface DietaryPreference {
    id: number;
    name: string;
    type: string;
    description: string;
}

interface Profile {
    id?: number;
}

interface DietaryPreferencesProps {
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
}: DietaryPreferencesProps) {
    const { data, setData, post, processing, errors } = useForm({
        dietary_preference_ids: selectedPreferences || [],
    });

    const togglePreference = (id: number) => {
        const current = data.dietary_preference_ids;
        setData(
            'dietary_preference_ids',
            current.includes(id)
                ? current.filter((p) => p !== id)
                : [...current, id],
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(onboarding.dietaryPreferences.store.url());
    };

    const renderPreferenceGroup = (
        title: string,
        items: DietaryPreference[] | undefined,
    ) => {
        if (!items || items.length === 0) return null;

        return (
            <div>
                <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
                    {title}
                </h3>
                <div className="grid gap-2 sm:grid-cols-2">
                    {items.map((preference) => (
                        <label
                            key={preference.id}
                            className={`flex cursor-pointer items-start rounded-lg border p-3 transition-colors ${
                                data.dietary_preference_ids.includes(
                                    preference.id,
                                )
                                    ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                    : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                            }`}
                        >
                            <input
                                type="checkbox"
                                checked={data.dietary_preference_ids.includes(
                                    preference.id,
                                )}
                                onChange={() => togglePreference(preference.id)}
                                className="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
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
                    ))}
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

                        <form onSubmit={submit} className="space-y-8">
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

                            {errors.dietary_preference_ids && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.dietary_preference_ids}
                                </p>
                            )}

                            {/* Submit Button */}
                            <div className="flex items-center justify-between border-t pt-6 dark:border-gray-700">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {data.dietary_preference_ids.length}{' '}
                                    preference
                                    {data.dietary_preference_ids.length !== 1
                                        ? 's'
                                        : ''}{' '}
                                    selected
                                </p>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    Continue to Health Conditions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
