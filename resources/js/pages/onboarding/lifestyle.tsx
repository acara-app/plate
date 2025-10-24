import onboarding from '@/routes/onboarding';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Lifestyle {
    id: number;
    name: string;
    activity_level: string;
    description: string;
    activity_multiplier: number;
}

interface Profile {
    lifestyle_id?: number;
}

interface LifestyleProps {
    profile?: Profile;
    lifestyles: Lifestyle[];
}

export default function Lifestyle({ profile, lifestyles }: LifestyleProps) {
    const { data, setData, post, processing, errors } = useForm({
        lifestyle_id: profile?.lifestyle_id || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(onboarding.lifestyle.store.url());
    };

    return (
        <>
            <Head title="Lifestyle - Step 3 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 3 of 5</span>
                            <span>60%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-3/5 rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            What's your lifestyle like?
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Help us understand your activity level to calculate
                            your daily calorie needs
                        </p>

                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-3">
                                {lifestyles.map((lifestyle) => (
                                    <label
                                        key={lifestyle.id}
                                        className="flex cursor-pointer flex-col rounded-lg border border-gray-300 p-4 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                    >
                                        <div className="flex items-start">
                                            <input
                                                type="radio"
                                                name="lifestyle_id"
                                                value={lifestyle.id}
                                                checked={
                                                    data.lifestyle_id ===
                                                    lifestyle.id
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        'lifestyle_id',
                                                        parseInt(
                                                            e.target.value,
                                                        ),
                                                    )
                                                }
                                                className="mt-1 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <div className="ml-3 flex-1">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {lifestyle.name}
                                                    </span>
                                                    <span className="text-sm text-gray-500 dark:text-gray-400">
                                                        {
                                                            lifestyle.activity_multiplier
                                                        }
                                                        x multiplier
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                                    {lifestyle.description}
                                                </p>
                                            </div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                            {errors.lifestyle_id && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {errors.lifestyle_id}
                                </p>
                            )}

                            {/* Submit Button */}
                            <div className="flex justify-end pt-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    Continue to Dietary Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
