@section('title', 'Your Complete Guide to the Free Diabetes Log Book | Acara Plate')
@section('meta_description', 'Discover how our free diabetes log book helps you take control of your health. Learn why tracking matters, what features make it effective, and how to use it to spot patterns and improve your diabetes management.')

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>

        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                <h1>Free Printable Diabetes Log Book</h1>
                
                <p class="lead">
                    Taking charge of your health starts with one simple step: understanding your body.
                </p>

                <p>
                    Managing your health is a team effort, and you are the most important player. A great way to feel more in control is to track your progress. Our <strong>Free Printable Diabetes Log Book</strong> is designed to be your personal tool, making it simple to see your patterns and celebrate your wins.
                </p>

                <div class="not-prose my-8">
                    <a
                        href="{{ route('diabetes-log-book') }}"
                        class="inline-flex items-center px-8 py-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold text-lg shadow-lg hover:shadow-xl"
                    >
                        <svg class="size-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Your Free Log Book Now
                    </a>
                </div>

                <h2>Here’s What Makes This Log Book So Helpful:</h2>

                <h3>Makes Tracking Simple</h3>
                <p>
                    No more forgetting! You get a clear, easy-to-use page for every day.
                </p>

                <h3>Be Your Own Health Detective</h3>
                <p>
                    By writing down your numbers, you can start to see what foods and activities help you feel great. It helps you spot patterns early.
                </p>

                <h3>Feel Ready and Proud at Doctor Visits</h3>
                <p>
                    Walk into your appointments organized. You'll have all your information in one place to share.
                </p>

                <h2>What's Inside Your Health Helper</h2>

                <p>This book is designed to be super easy and useful:</p>

                <ul>
                    <li><strong>Your 4-Key Time Check-Ins:</strong> Special spots to write your levels before and after Breakfast, Lunch, Dinner, and Bedtime.</li>
                    <li><strong>Notes Section:</strong> Perfect for writing down what you ate, how you felt, or questions for your care team.</li>
                    <li><strong>Clean & Clear Design:</strong> Big, simple spaces so you can write your numbers fast.</li>
                    <li><strong>Take It Anywhere:</strong> The perfect size (6"×9") to fit in your bag.</li>
                    <li><strong>Built to Last:</strong> Made with strong, soft cover and thick paper that won't bleed through.</li>
                </ul>

                <div class="bg-blue-50 dark:bg-blue-950 border-l-4 border-blue-500 p-6 my-8 rounded-r-lg">
                    <p class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        💡 A Helpful Tip
                    </p>
                    <p class="text-blue-800 dark:text-blue-200 mb-0">
                        Try writing down a quick note about your meals or how you're feeling next to your numbers. It's a powerful way to learn how your unique body responds!
                    </p>
                </div>

                <p class="text-xl font-semibold">
                    You can start taking charge of your health right now. This log book is a powerful first step.
                </p>

                <div class="not-prose my-8 text-center">
                    <a
                        href="{{ route('diabetes-log-book') }}"
                        class="inline-flex items-center px-8 py-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold text-lg shadow-lg hover:shadow-xl"
                    >
                        <svg class="size-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Get Started - Print Your Log Book
                    </a>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-lg my-8">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-0">
                        <strong>Important:</strong> This log book is a tool for tracking and does not replace medical advice. Always consult with your healthcare provider about your diabetes management plan.
                    </p>
                </div>

                <div class="not-prose mt-16 border-t border-slate-200 pt-12 dark:border-slate-700">
                    <h2 class="text-center text-2xl font-bold text-slate-900 dark:text-white mb-8">Explore Our Other Free Tools</h2>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <a href="{{ route('spike-calculator') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md hover:ring-emerald-500 dark:bg-slate-800 dark:ring-slate-700 dark:hover:ring-emerald-500">
                            <span class="mb-3 text-4xl">⚡️</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Spike Calculator</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Check if foods will spike your blood sugar and get better alternatives.</p>
                        </a>
                        <a href="{{ route('snap-to-track') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md hover:ring-orange-500 dark:bg-slate-800 dark:ring-slate-700 dark:hover:ring-orange-500">
                            <span class="mb-3 text-4xl">📸</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Snap to Track</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Snap a photo of your meal to get an instant calorie and macro breakdown.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-footer />
</x-default-layout>
