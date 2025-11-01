import { Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import mealPlans from '@/routes/meal-plans';

export default function Completion() {
    return (
        <>
            <Head title="Welcome Aboard!" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-linear-to-br from-blue-50 to-indigo-100 px-4 py-12 sm:px-6 lg:px-8 dark:from-gray-900 dark:to-gray-800">
                <div className="w-full max-w-2xl text-center">
                    {/* Confetti Animation */}
                    <div className="mb-8 animate-bounce text-8xl">🎉</div>

                    {/* Success Message */}
                    <h1 className="mb-4 text-4xl font-bold text-gray-900 sm:text-5xl dark:text-white">
                        You're All Set!
                    </h1>
                    <p className="mb-8 text-lg text-gray-600 sm:text-xl dark:text-gray-300">
                        Your personalized nutrition journey starts now. We're
                        creating your meal plan right now!
                    </p>

                    {/* Decorative Confetti Emojis */}
                    <div className="mb-12 flex justify-center gap-4 text-4xl">
                        <span className="animate-pulse">🎊</span>
                        <span className="animate-bounce delay-100">✨</span>
                        <span className="animate-pulse delay-200">🌟</span>
                        <span className="animate-bounce delay-300">🎈</span>
                    </div>

                    {/* Next Steps Card */}
                    <div className="mx-auto mb-8 max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                        <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                            What's Next?
                        </h2>
                        <div className="space-y-4">
                            <div>
                                <Link href={mealPlans.weekly().url}>
                                    <Button className="w-full" size="lg">
                                        View Your Meal Plan
                                    </Button>
                                </Link>
                            </div>

                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Your personalized meal plan will be ready in a
                                few moments
                            </p>
                        </div>
                    </div>

                    {/* Additional Info */}
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        You can always update your preferences and profile
                        information in your settings.
                    </p>
                </div>
            </div>

            <style>{`
                @keyframes bounce {
                    0%, 100% {
                        transform: translateY(0);
                    }
                    50% {
                        transform: translateY(-20px);
                    }
                }
                
                .delay-100 {
                    animation-delay: 100ms;
                }
                
                .delay-200 {
                    animation-delay: 200ms;
                }
                
                .delay-300 {
                    animation-delay: 300ms;
                }
            `}</style>
        </>
    );
}
