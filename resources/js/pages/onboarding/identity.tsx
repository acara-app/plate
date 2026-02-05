import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Form, Head, Link } from '@inertiajs/react';
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

import { useTranslation } from 'react-i18next';

interface Props {
    profile?: Profile;
}

export default function Identity({ profile }: Props) {
    const { t } = useTranslation('common');

    const GOAL_OPTIONS = [
        {
            value: GoalChoice.Spikes,
            label: t('onboarding.identity.options.goals.spikes'),
            description: t('onboarding.identity.options.goals.spikes_desc'),
        },
        {
            value: GoalChoice.WeightLoss,
            label: t('onboarding.identity.options.goals.weight_loss'),
            description: t(
                'onboarding.identity.options.goals.weight_loss_desc',
            ),
        },
        {
            value: GoalChoice.HeartHealth,
            label: t('onboarding.identity.options.goals.heart_health'),
            description: t(
                'onboarding.identity.options.goals.heart_health_desc',
            ),
        },
        {
            value: GoalChoice.BuildMuscle,
            label: t('onboarding.identity.options.goals.build_muscle'),
            description: t(
                'onboarding.identity.options.goals.build_muscle_desc',
            ),
        },
        {
            value: GoalChoice.HealthyEating,
            label: t('onboarding.identity.options.goals.healthy_eating'),
            description: t(
                'onboarding.identity.options.goals.healthy_eating_desc',
            ),
        },
    ];

    const ANIMAL_PRODUCT_OPTIONS = [
        {
            value: AnimalProductChoice.Omnivore,
            label: t('onboarding.identity.options.animal_products.omnivore'),
        },
        {
            value: AnimalProductChoice.Pescatarian,
            label: t('onboarding.identity.options.animal_products.pescatarian'),
        },
        {
            value: AnimalProductChoice.Vegan,
            label: t('onboarding.identity.options.animal_products.vegan'),
        },
    ];

    const INTENSITY_OPTIONS = [
        {
            value: IntensityChoice.Balanced,
            label: t('onboarding.identity.options.intensity.balanced'),
        },
        {
            value: IntensityChoice.Aggressive,
            label: t('onboarding.identity.options.intensity.aggressive'),
        },
    ];

    return (
        <>
            <Head title={t('onboarding.identity.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl">
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>{t('onboarding.identity.step')}</span>
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
                            {t('onboarding.identity.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.identity.description')}
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
                                            {t(
                                                'onboarding.identity.questions.mission',
                                            )}
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
                                            {t(
                                                'onboarding.identity.questions.animal_products',
                                            )}
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
                                            {t(
                                                'onboarding.identity.questions.intensity',
                                            )}
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
                                        {t('onboarding.identity.submit')}
                                    </Button>

                                    <div className="flex justify-center">
                                        <Link
                                            href={dashboard.url()}
                                            className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                        >
                                            {t('onboarding.biometrics.exit')}
                                        </Link>
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
