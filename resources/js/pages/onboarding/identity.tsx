import onboarding from '@/routes/onboarding';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

import {
    AnimalProductChoice,
    GoalChoice,
    IntensityChoice,
    Profile,
} from '@/types';

const GOAL_OPTIONS = [
    {
        value: GoalChoice.Spikes,
        label: 'Control Spikes',
        description: 'Focus: Stable Blood Sugar',
    },
    {
        value: GoalChoice.WeightLoss,
        label: 'Deep Weight Loss',
        description: 'Focus: Burning Fat',
    },
    {
        value: GoalChoice.HeartHealth,
        label: 'Heart Health',
        description: 'Focus: Cholesterol/BP',
    },
    {
        value: GoalChoice.BuildMuscle,
        label: 'Build Muscle',
        description: 'Focus: Strength & Hypertrophy',
    },
    {
        value: GoalChoice.HealthyEating,
        label: 'Just Healthy Eating',
        description: 'Maintenance / No specific goal',
    },
];

const ANIMAL_PRODUCT_OPTIONS = [
    {
        value: AnimalProductChoice.Omnivore,
        label: 'I love meat/fish.',
    },
    {
        value: AnimalProductChoice.Pescatarian,
        label: 'I prefer plants, but eat fish/eggs.',
    },
    {
        value: AnimalProductChoice.Vegan,
        label: 'Strictly plants only.',
    },
];

const INTENSITY_OPTIONS = [
    {
        value: IntensityChoice.Balanced,
        label: 'Balanced (Sustainable)',
    },
    {
        value: IntensityChoice.Aggressive,
        label: 'Aggressive (Fast Results)',
    },
];

interface Props {
    profile?: Profile;
}

export default function Identity({ profile }: Props) {
    return (
        <>
            <Head title="Let's Create Your Perfect Plan" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 2 of 3</span>
                            <span>66%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-[66%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            Let's Create Your Perfect Plan
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Answer 3 quick questions to generate your
                            personalized meal plan.
                        </p>

                        <Form
                            {...onboarding.identity.store.form()}
                            disableWhileProcessing
                            className="space-y-8"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                                            1. What is your primary mission?
                                        </h2>
                                        <div className="space-y-3">
                                            {GOAL_OPTIONS.map((option) => (
                                                <label
                                                    key={option.value}
                                                    className={cn(
                                                        'flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors',
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                        'border-gray-300 dark:border-gray-600',
                                                    )}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="goal_choice"
                                                        value={option.value}
                                                        required
                                                        defaultChecked={
                                                            profile?.goal_choice ===
                                                            option.value
                                                        }
                                                        className="h-5 w-5 border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <div>
                                                        <div className="font-medium text-gray-900 dark:text-white">
                                                            {option.label}
                                                        </div>
                                                        <div className="text-sm text-gray-600 dark:text-gray-400">
                                                            {option.description}
                                                        </div>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError
                                            message={errors.goal_choice}
                                        />
                                    </div>

                                    <div>
                                        <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                                            2. How do you feel about animal
                                            products?
                                        </h2>
                                        <div className="space-y-3">
                                            {ANIMAL_PRODUCT_OPTIONS.map(
                                                (option) => (
                                                    <label
                                                        key={option.value}
                                                        className={cn(
                                                            'flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors',
                                                            'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                            'border-gray-300 dark:border-gray-600',
                                                        )}
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="animal_product_choice"
                                                            value={option.value}
                                                            required
                                                            defaultChecked={
                                                                profile?.animal_product_choice ===
                                                                option.value
                                                            }
                                                            className="h-5 w-5 border-gray-300 text-primary focus:ring-primary"
                                                        />
                                                        <div className="text-gray-900 dark:text-white">
                                                            {option.label}
                                                        </div>
                                                    </label>
                                                ),
                                            )}
                                        </div>
                                        <InputError
                                            message={
                                                errors.animal_product_choice
                                            }
                                        />
                                    </div>

                                    <div>
                                        <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                                            3. Do you want a balanced lifestyle
                                            or an aggressive reset?
                                        </h2>
                                        <div className="space-y-3">
                                            {INTENSITY_OPTIONS.map((option) => (
                                                <label
                                                    key={option.value}
                                                    className={cn(
                                                        'flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors',
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                        'border-gray-300 dark:border-gray-600',
                                                    )}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="intensity_choice"
                                                        value={option.value}
                                                        required
                                                        defaultChecked={
                                                            profile?.intensity_choice ===
                                                            option.value
                                                        }
                                                        className="h-5 w-5 border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <div className="text-gray-900 dark:text-white">
                                                        {option.label}
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError
                                            message={errors.intensity_choice}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full"
                                    >
                                        {processing && (
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                        )}
                                        Generate My Meal Plan
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
