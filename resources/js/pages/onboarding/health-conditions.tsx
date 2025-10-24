import onboarding from '@/routes/onboarding';
import { HealthCondition, Profile } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Props {
    profile?: Profile;
    selectedConditions: number[];
    healthConditions: HealthCondition[];
}

export default function HealthConditions({
    selectedConditions,
    healthConditions,
}: Props) {
    const [notes, setNotes] = useState<Record<number, string>>({});
    const [expandedId, setExpandedId] = useState<number | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        health_condition_ids: selectedConditions || [],
        notes: [] as string[],
    });

    const toggleCondition = (id: number) => {
        const current = data.health_condition_ids;
        if (current.includes(id)) {
            // Remove note when unchecking
            const newNotes = { ...notes };
            delete newNotes[id];
            setNotes(newNotes);
            setData(
                'health_condition_ids',
                current.filter((c) => c !== id),
            );
        } else {
            setData('health_condition_ids', [...current, id]);
        }
    };

    const updateNote = (id: number, value: string) => {
        setNotes((prev) => ({ ...prev, [id]: value }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const notesArray = data.health_condition_ids.map(
            (id) => notes[id] || '',
        );
        setData('notes', notesArray);

        post(onboarding.healthConditions.store.url());
    };

    return (
        <>
            <Head title="Health Conditions - Step 5 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-4xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 5 of 5</span>
                            <span>100%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-full rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            Health conditions
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Select any health conditions that may affect your
                            nutritional needs
                        </p>

                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-3">
                                {healthConditions.map((condition) => (
                                    <div
                                        key={condition.id}
                                        className={`rounded-lg border p-4 transition-colors ${
                                            data.health_condition_ids.includes(
                                                condition.id,
                                            )
                                                ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                : 'border-gray-300 dark:border-gray-600'
                                        }`}
                                    >
                                        <label className="flex cursor-pointer items-start">
                                            <input
                                                type="checkbox"
                                                checked={data.health_condition_ids.includes(
                                                    condition.id,
                                                )}
                                                onChange={() =>
                                                    toggleCondition(
                                                        condition.id,
                                                    )
                                                }
                                                className="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
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
                                                        className="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                                    >
                                                        {expandedId ===
                                                        condition.id
                                                            ? 'Hide'
                                                            : 'Info'}
                                                    </button>
                                                </div>
                                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                                    {condition.description}
                                                </p>

                                                {expandedId ===
                                                    condition.id && (
                                                    <div className="mt-2 rounded-md bg-blue-50 p-3 text-sm text-gray-700 dark:bg-blue-900/30 dark:text-gray-300">
                                                        <p className="font-medium">
                                                            Nutritional Impact:
                                                        </p>
                                                        <p className="mt-1">
                                                            {
                                                                condition.nutritional_impact
                                                            }
                                                        </p>
                                                    </div>
                                                )}

                                                {data.health_condition_ids.includes(
                                                    condition.id,
                                                ) && (
                                                    <div className="mt-3">
                                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Additional notes
                                                            (optional)
                                                        </label>
                                                        <textarea
                                                            value={
                                                                notes[
                                                                    condition.id
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
                                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                            placeholder="Any specific details about this condition..."
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                        </label>
                                    </div>
                                ))}
                            </div>

                            {errors.health_condition_ids && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.health_condition_ids}
                                </p>
                            )}

                            {/* Submit Button */}
                            <div className="flex items-center justify-between border-t pt-6 dark:border-gray-700">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {data.health_condition_ids.length > 0
                                        ? `${data.health_condition_ids.length} condition${data.health_condition_ids.length !== 1 ? 's' : ''} selected`
                                        : "No conditions selected - that's perfectly fine!"}
                                </p>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    Complete Onboarding
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
