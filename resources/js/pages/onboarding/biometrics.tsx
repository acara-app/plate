import onboarding from '@/routes/onboarding';
import { Profile, SexOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    profile: Profile;
    sexOptions: SexOption[];
}

export default function Biometrics({ profile, sexOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        age: profile?.age || '',
        height: profile?.height || '',
        weight: profile?.weight || '',
        sex: profile?.sex || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(onboarding.biometrics.store.url());
    };

    return (
        <>
            <Head title="Biometrics - Step 1 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-md">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 1 of 5</span>
                            <span>20%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-1/5 rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            Tell us about yourself
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            We'll use this information to calculate your
                            nutritional needs
                        </p>

                        <form onSubmit={submit} className="space-y-6">
                            {/* Age */}
                            <div>
                                <label
                                    htmlFor="age"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Age
                                </label>
                                <input
                                    id="age"
                                    type="number"
                                    value={data.age}
                                    onChange={(e) =>
                                        setData('age', e.target.value)
                                    }
                                    min="13"
                                    max="120"
                                    required
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Enter your age"
                                />
                                {errors.age && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.age}
                                    </p>
                                )}
                            </div>

                            {/* Height */}
                            <div>
                                <label
                                    htmlFor="height"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Height (cm)
                                </label>
                                <input
                                    id="height"
                                    type="number"
                                    step="0.01"
                                    value={data.height}
                                    onChange={(e) =>
                                        setData('height', e.target.value)
                                    }
                                    min="50"
                                    max="300"
                                    required
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Enter your height in centimeters"
                                />
                                {errors.height && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.height}
                                    </p>
                                )}
                            </div>

                            {/* Weight */}
                            <div>
                                <label
                                    htmlFor="weight"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Weight (kg)
                                </label>
                                <input
                                    id="weight"
                                    type="number"
                                    step="0.01"
                                    value={data.weight}
                                    onChange={(e) =>
                                        setData('weight', e.target.value)
                                    }
                                    min="20"
                                    max="500"
                                    required
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Enter your weight in kilograms"
                                />
                                {errors.weight && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.weight}
                                    </p>
                                )}
                            </div>

                            {/* Sex */}
                            <div>
                                <label
                                    htmlFor="sex"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Biological Sex
                                </label>
                                <select
                                    id="sex"
                                    value={data.sex}
                                    onChange={(e) =>
                                        setData('sex', e.target.value)
                                    }
                                    required
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">
                                        Select your biological sex
                                    </option>
                                    {sexOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.sex && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.sex}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Used for accurate calorie calculations
                                </p>
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    Continue to Goals
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
