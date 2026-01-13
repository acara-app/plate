import onboarding from '@/routes/onboarding';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

const DAY_OPTIONS = [1, 2, 3, 4, 5, 6, 7] as const;

export default function MealPlanDuration() {
    const { t } = useTranslation('common');
    const [selectedDays, setSelectedDays] = useState<number>(7);

    return (
        <>
            <Head title={t('onboarding.meal_plan_duration.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 7,
                                    total: 7,
                                })}
                            </span>
                            <span>100%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-full overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.meal_plan_duration.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.meal_plan_duration.description')}
                        </p>

                        <Form
                            {...onboarding.mealPlanDuration.store.form()}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="meal_plan_days"
                                        value={selectedDays}
                                    />

                                    <div>
                                        <label className="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {t(
                                                'onboarding.meal_plan_duration.days_label',
                                            )}
                                        </label>
                                        <div className="grid grid-cols-7 gap-2">
                                            {DAY_OPTIONS.map((days) => (
                                                <button
                                                    key={days}
                                                    type="button"
                                                    onClick={() =>
                                                        setSelectedDays(days)
                                                    }
                                                    className={cn(
                                                        'flex h-14 w-full flex-col items-center justify-center rounded-lg border-2 text-center transition-all',
                                                        selectedDays === days
                                                            ? 'border-primary bg-primary/10 text-primary shadow-[0_0_12px_rgba(16,185,129,0.3)] dark:border-primary dark:bg-primary/20'
                                                            : 'border-gray-300 bg-white hover:border-primary/50 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-primary/50 dark:hover:bg-gray-600',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'text-xl font-bold',
                                                            selectedDays ===
                                                                days
                                                                ? 'text-primary'
                                                                : 'text-gray-900 dark:text-white',
                                                        )}
                                                    >
                                                        {days}
                                                    </span>
                                                    <span
                                                        className={cn(
                                                            'text-xs',
                                                            selectedDays ===
                                                                days
                                                                ? 'text-primary/80'
                                                                : 'text-gray-500 dark:text-gray-400',
                                                        )}
                                                    >
                                                        {days === 1
                                                            ? t(
                                                                  'onboarding.meal_plan_duration.day',
                                                                  { count: 1 },
                                                              )
                                                            : t(
                                                                  'onboarding.meal_plan_duration.days',
                                                                  {
                                                                      count: days,
                                                                  },
                                                              )}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                        <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                            {t(
                                                'onboarding.meal_plan_duration.days_hint',
                                            )}
                                        </p>
                                    </div>

                                    {/* Selected Days Summary */}
                                    <div className="rounded-lg border border-primary/30 bg-primary/5 p-4 dark:border-primary/50 dark:bg-primary/10">
                                        <p className="text-center text-lg font-medium text-gray-900 dark:text-white">
                                            {t(
                                                'onboarding.meal_plan_duration.summary',
                                                { count: selectedDays },
                                            )}
                                        </p>
                                    </div>

                                    {/* Submit Button */}
                                    <div className="flex justify-end border-t pt-6 dark:border-gray-700">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="relative inline-flex items-center overflow-hidden rounded-md bg-primary px-6 py-3 text-base font-medium text-primary-foreground shadow-[0_0_20px_rgba(16,185,129,0.3),inset_0_1px_0_rgba(255,255,255,0.4),inset_0_-1px_2px_rgba(0,0,0,0.2)] transition-all before:absolute before:inset-0 before:bg-linear-to-br before:from-white/30 before:via-transparent before:to-transparent after:absolute after:inset-0 after:bg-linear-to-tl after:from-black/10 after:via-transparent after:to-white/10 hover:shadow-[0_0_30px_rgba(16,185,129,0.5),inset_0_1px_0_rgba(255,255,255,0.5),inset_0_-1px_2px_rgba(0,0,0,0.2)] hover:brightness-110 focus:ring-[3px] focus:ring-primary/50 focus:outline-none active:brightness-95"
                                        >
                                            {processing && (
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                            )}
                                            <span className="relative z-10">
                                                {t(
                                                    'onboarding.meal_plan_duration.complete',
                                                )}
                                            </span>
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
