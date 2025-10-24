import onboarding from '@/routes/onboarding';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Goal {
    id: number;
    name: string;
}

interface Profile {
    goal_id?: number;
    target_weight?: number;
    additional_goals?: string;
}

interface GoalsProps {
    profile?: Profile;
    goals: Goal[];
}

export default function Goals({ profile, goals }: GoalsProps) {
    const { data, setData, post, processing, errors } = useForm({
        goal_id: profile?.goal_id || '',
        target_weight: profile?.target_weight || '',
        additional_goals: profile?.additional_goals || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(onboarding.goals.store.url());
    };

    return (
        <>
            <Head title="Goals - Step 2 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-md">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 2 of 5</span>
                            <span>40%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-2/5 rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            What are your goals?
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Select your primary nutrition goal
                        </p>

                        <form onSubmit={submit} className="space-y-6">
                            {/* Goal Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Primary Goal
                                </label>
                                <div className="mt-2 space-y-2">
                                    {goals.map((goal) => (
                                        <label
                                            key={goal.id}
                                            className="flex cursor-pointer items-center rounded-lg border border-gray-300 p-4 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                        >
                                            <input
                                                type="radio"
                                                name="goal_id"
                                                value={goal.id}
                                                checked={
                                                    data.goal_id === goal.id
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        'goal_id',
                                                        parseInt(
                                                            e.target.value,
                                                        ),
                                                    )
                                                }
                                                className="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="ml-3 text-gray-900 dark:text-white">
                                                {goal.name}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                {errors.goal_id && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.goal_id}
                                    </p>
                                )}
                            </div>

                            {/* Target Weight (Optional) */}
                            <div>
                                <label
                                    htmlFor="target_weight"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Target Weight (kg) - Optional
                                </label>
                                <input
                                    id="target_weight"
                                    type="number"
                                    step="0.01"
                                    value={data.target_weight}
                                    onChange={(e) =>
                                        setData('target_weight', e.target.value)
                                    }
                                    min="20"
                                    max="500"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Enter your target weight"
                                />
                                {errors.target_weight && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.target_weight}
                                    </p>
                                )}
                            </div>

                            {/* Additional Goals (Optional) */}
                            <div>
                                <label
                                    htmlFor="additional_goals"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Additional Goals - Optional
                                </label>
                                <textarea
                                    id="additional_goals"
                                    value={data.additional_goals}
                                    onChange={(e) =>
                                        setData(
                                            'additional_goals',
                                            e.target.value,
                                        )
                                    }
                                    rows={4}
                                    maxLength={1000}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Tell us about any other goals or specific needs..."
                                />
                                {errors.additional_goals && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.additional_goals}
                                    </p>
                                )}
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    Continue to Lifestyle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
