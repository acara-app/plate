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
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
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
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
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
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
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
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
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
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
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
                                className="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Let's Get Started
                            </Link>
                        </div>
                    </div>

                    <p className="text-center text-sm text-gray-500 dark:text-gray-400">
                        This should take about 5-10 minutes to complete
                    </p>
                </div>
            </div>
        </>
    );
}
