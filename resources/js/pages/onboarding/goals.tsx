import onboarding from '@/routes/onboarding';
import { Goal, Profile } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

interface Props {
    goals: Goal[];
    profile?: Profile;
}

export default function Goals({ profile, goals }: Props) {
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
                            <div className="relative h-2 w-2/5 overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            What are your goals?
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Select your primary nutrition goal
                        </p>

                        <Form
                            {...onboarding.goals.store.form()}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Goal Selection */}
                                    <div className="grid gap-2">
                                        <Label>Primary Goal</Label>
                                        <div className="space-y-2">
                                            {goals.map((goal) => (
                                                <label
                                                    key={goal.id}
                                                    className={cn(
                                                        'flex cursor-pointer items-center rounded-lg border p-4 transition-colors',
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                        'border-gray-300 dark:border-gray-600',
                                                    )}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="goal_id"
                                                        value={goal.id}
                                                        defaultChecked={
                                                            profile?.goal_id ===
                                                            goal.id
                                                        }
                                                        className="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <span className="ml-3 text-gray-900 dark:text-white">
                                                        {goal.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError message={errors.goal_id} />
                                    </div>

                                    {/* Target Weight (Optional) */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="target_weight">
                                            Target Weight (kg) - Optional
                                        </Label>
                                        <Input
                                            id="target_weight"
                                            type="number"
                                            name="target_weight"
                                            defaultValue={
                                                profile?.target_weight || ''
                                            }
                                            step="0.01"
                                            min="20"
                                            max="500"
                                            placeholder="Enter your target weight"
                                        />
                                        <InputError
                                            message={errors.target_weight}
                                        />
                                    </div>

                                    {/* Additional Goals (Optional) */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="additional_goals">
                                            Additional Goals - Optional
                                        </Label>
                                        <Textarea
                                            id="additional_goals"
                                            name="additional_goals"
                                            defaultValue={
                                                profile?.additional_goals || ''
                                            }
                                            rows={4}
                                            maxLength={1000}
                                            placeholder="Tell us about any other goals or specific needs..."
                                        />
                                        <InputError
                                            message={errors.additional_goals}
                                        />
                                    </div>

                                    {/* Submit Button */}
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            Continue to Lifestyle
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
