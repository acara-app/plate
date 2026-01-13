import { privacy, terms } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Questionnaire() {
    const { t } = useTranslation('common');

    return (
        <>
            <Head title={t('onboarding.questionnaire.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl space-y-8">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {t('onboarding.questionnaire.heading')}
                        </h1>
                        <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            {t('onboarding.questionnaire.description')}
                        </p>
                    </div>

                    <div className="mt-12 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h2 className="mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                            {t('onboarding.questionnaire.what_to_expect')}
                        </h2>

                        <div className="space-y-4">
                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    1
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step1_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step1_description',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    2
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step2_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step2_description',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    3
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step3_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step3_description',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    4
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step4_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step4_description',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    5
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step5_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step5_description',
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    6
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        {t(
                                            'onboarding.questionnaire.step6_title',
                                        )}
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        {t(
                                            'onboarding.questionnaire.step6_description',
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 flex justify-center">
                            <Link
                                href={onboarding.biometrics.show.url()}
                                className="relative inline-flex items-center overflow-hidden rounded-md bg-primary px-6 py-3 text-base font-medium text-primary-foreground shadow-[0_0_20px_rgba(16,185,129,0.3),inset_0_1px_0_rgba(255,255,255,0.4),inset_0_-1px_2px_rgba(0,0,0,0.2)] transition-all before:absolute before:inset-0 before:bg-linear-to-br before:from-white/30 before:via-transparent before:to-transparent after:absolute after:inset-0 after:bg-linear-to-tl after:from-black/10 after:via-transparent after:to-white/10 hover:shadow-[0_0_30px_rgba(16,185,129,0.5),inset_0_1px_0_rgba(255,255,255,0.5),inset_0_-1px_2px_rgba(0,0,0,0.2)] hover:brightness-110 focus:ring-[3px] focus:ring-primary/50 focus:outline-none active:brightness-95"
                            >
                                <span className="relative z-10">
                                    {t('onboarding.questionnaire.get_started')}
                                </span>
                            </Link>
                        </div>
                    </div>

                    <p className="text-center text-sm text-gray-500 dark:text-gray-400">
                        {t('onboarding.questionnaire.time_estimate')}
                    </p>

                    <p className="text-center text-sm text-gray-600 dark:text-gray-400">
                        {t('onboarding.questionnaire.terms_agreement')}{' '}
                        <Link
                            href={terms.url()}
                            className="text-primary underline hover:text-primary/80"
                        >
                            {t('onboarding.questionnaire.terms_of_use')}
                        </Link>{' '}
                        {t('onboarding.questionnaire.and')}{' '}
                        <Link
                            href={privacy.url()}
                            className="text-primary underline hover:text-primary/80"
                        >
                            {t('onboarding.questionnaire.privacy_policy')}
                        </Link>
                        .
                    </p>
                </div>
            </div>
        </>
    );
}
