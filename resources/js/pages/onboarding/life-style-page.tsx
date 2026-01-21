import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { LifeStyle, Profile } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface Props {
    profile?: Profile;
    lifestyles: LifeStyle[];
}

export default function LifeStylePage({ profile, lifestyles }: Props) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();
    return (
        <>
            <Head title={t('onboarding.lifestyle.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 3,
                                    total: 7,
                                })}
                            </span>
                            <span>43%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-[43%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.lifestyle.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.lifestyle.description')}
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
                                                                {t(
                                                                    'onboarding.lifestyle.multiplier',
                                                                    {
                                                                        value: lifestyle.activity_multiplier,
                                                                    },
                                                                )}
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

                                    {/* Footer Section */}
                                    <div className="flex flex-col items-center gap-4">
                                        {/* Action Buttons Row */}
                                        <div className="flex justify-center gap-3">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() => {
                                                    router.post(
                                                        onboarding.lifestyle.store.url(),
                                                        {},
                                                    );
                                                }}
                                                className="min-w-[100px]"
                                            >
                                                {t(
                                                    'onboarding.lifestyle.skip',
                                                    {
                                                        defaultValue: 'Skip',
                                                    },
                                                )}
                                            </Button>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="min-w-[120px]"
                                            >
                                                {processing && (
                                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                                )}
                                                {t(
                                                    'onboarding.lifestyle.continue',
                                                )}
                                            </Button>
                                        </div>

                                        {/* Exit Link - Centered Below */}
                                        {currentUser?.has_meal_plan && (
                                            <Link
                                                href={dashboard.url()}
                                                className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                            >
                                                {t('onboarding.lifestyle.exit')}
                                            </Link>
                                        )}
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
