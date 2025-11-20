import { privacy, terms } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Head, Link } from '@inertiajs/react';

export default function Questionnaire() {
    return (
        <>
            <Head title="Take the Questionnaire" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-2xl space-y-8">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Welcome to Your Personalized Nutrition Journey
                        </h1>
                        <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            Let's get to know you better to create a customized
                            meal plan that fits your unique needs and goals.
                        </p>
                    </div>

                    <div className="mt-12 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h2 className="mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                            What to Expect
                        </h2>

                        <div className="space-y-4">
                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    1
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        Biometrics
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Share your age, height, weight, and
                                        biological sex
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    2
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        Goals
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Tell us about your nutrition and fitness
                                        goals
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    3
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        Lifestyle
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Describe your activity level and daily
                                        routine
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    4
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        Dietary Preferences
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Let us know about allergies,
                                        intolerances, and food preferences
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                    5
                                </div>
                                <div className="ml-4">
                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                        Health Conditions
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Share any health conditions that affect
                                        your nutrition
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
                                    Let's Get Started
                                </span>
                            </Link>
                        </div>
                    </div>

                    <p className="text-center text-sm text-gray-500 dark:text-gray-400">
                        This should take about 5-10 minutes to complete
                    </p>

                    <p className="text-center text-sm text-gray-600 dark:text-gray-400">
                        By continuing, you confirm and guarantee that you have
                        read, understood, and agreed to our{' '}
                        <Link
                            href={terms.url()}
                            className="text-primary underline hover:text-primary/80"
                        >
                            Terms of Use
                        </Link>{' '}
                        and{' '}
                        <Link
                            href={privacy.url()}
                            className="text-primary underline hover:text-primary/80"
                        >
                            Privacy Policy
                        </Link>
                        .
                    </p>
                </div>
            </div>
        </>
    );
}
