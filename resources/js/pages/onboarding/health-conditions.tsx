import onboarding from '@/routes/onboarding';
import { HealthCondition, Profile } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

interface Props {
    profile?: Profile;
    selectedConditions: number[];
    healthConditions: HealthCondition[];
}

export default function HealthConditions({
    selectedConditions,
    healthConditions,
}: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>(
        selectedConditions || [],
    );
    const [notes, setNotes] = useState<Record<number, string>>({});
    const [expandedId, setExpandedId] = useState<number | null>(null);

    const toggleCondition = (id: number) => {
        if (selectedIds.includes(id)) {
            // Remove note when unchecking
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

                        <Form
                            {...onboarding.healthConditions.store.form()}
                            disableWhileProcessing
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Hidden inputs for selected IDs */}
                                    {selectedIds.map((id) => (
                                        <input
                                            key={`id-${id}`}
                                            type="hidden"
                                            name="health_condition_ids[]"
                                            value={id}
                                        />
                                    ))}

                                    {/* Hidden inputs for notes */}
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
                                            const isSelected =
                                                selectedIds.includes(
                                                    condition.id,
                                                );
                                            return (
                                                <div
                                                    key={condition.id}
                                                    className={cn(
                                                        'rounded-lg border p-4 transition-colors',
                                                        isSelected
                                                            ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                            : 'border-gray-300 dark:border-gray-600',
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
                                                            className="mt-1"
                                                        />
                                                        <div className="ml-3 flex-1">
                                                            <div className="flex items-center justify-between">
                                                                <span className="font-medium text-gray-900 dark:text-white">
                                                                    {
                                                                        condition.name
                                                                    }
                                                                </span>
                                                                <button
                                                                    type="button"
                                                                    onClick={(
                                                                        e,
                                                                    ) => {
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
                                                                {
                                                                    condition.description
                                                                }
                                                            </p>

                                                            {expandedId ===
                                                                condition.id && (
                                                                <div className="mt-2 rounded-md bg-blue-50 p-3 text-sm text-gray-700 dark:bg-blue-900/30 dark:text-gray-300">
                                                                    <p className="font-medium">
                                                                        Nutritional
                                                                        Impact:
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
                                                                        Additional
                                                                        notes
                                                                        (optional)
                                                                    </label>
                                                                    <textarea
                                                                        value={
                                                                            notes[
                                                                                condition
                                                                                    .id
                                                                            ] ||
                                                                            ''
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) =>
                                                                            updateNote(
                                                                                condition.id,
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        rows={2}
                                                                        maxLength={
                                                                            500
                                                                        }
                                                                        className={cn(
                                                                            'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs transition-colors',
                                                                            'placeholder:text-gray-500 dark:placeholder:text-gray-400',
                                                                            'focus-visible:border-blue-500 focus-visible:ring-[3px] focus-visible:ring-blue-500/50 focus-visible:outline-none',
                                                                            'disabled:cursor-not-allowed disabled:opacity-50',
                                                                            'dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                                                        )}
                                                                        placeholder="Any specific details about this condition..."
                                                                    />
                                                                </div>
                                                            )}
                                                        </div>
                                                    </label>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    <InputError
                                        message={errors.health_condition_ids}
                                    />

                                    {/* Submit Button */}
                                    <div className="flex items-center justify-between border-t pt-6 dark:border-gray-700">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {selectedIds.length > 0
                                                ? `${selectedIds.length} condition${selectedIds.length !== 1 ? 's' : ''} selected`
                                                : "No conditions selected - that's perfectly fine!"}
                                        </p>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            Complete Onboarding
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
