import onboarding from '@/routes/onboarding';
import { LifeStyle, Profile } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Props {
    profile?: Profile;
    lifestyles: LifeStyle[];
}

export default function LifeStylePage({ profile, lifestyles }: Props) {
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

                        <Form
                            {...onboarding.lifestyle.store.form()}
                            disableWhileProcessing
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-3">
                                        {lifestyles.map((lifestyle) => (
                                            <label
                                                key={lifestyle.id}
                                                className={cn(
                                                    'flex cursor-pointer flex-col rounded-lg border p-4 transition-colors',
                                                    'hover:bg-gray-50 dark:hover:bg-gray-700',
                                                    'border-gray-300 dark:border-gray-600',
                                                )}
                                            >
                                                <div className="flex items-start">
                                                    <input
                                                        type="radio"
                                                        name="lifestyle_id"
                                                        value={lifestyle.id}
                                                        defaultChecked={
                                                            profile?.lifestyle_id ===
                                                            lifestyle.id
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
                                                            {
                                                                lifestyle.description
                                                            }
                                                        </p>
                                                    </div>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.lifestyle_id} />

                                    {/* Submit Button */}
                                    <div className="flex justify-end pt-4">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            Continue to Dietary Preferences
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
