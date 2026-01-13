@section('title', 'Free Printable Diabetes Log Book (PDF Download) | Digital Tracker')
@section('meta_description', 'Don\'t wait for mail—download and print your free diabetes log book instantly. Track blood sugar, insulin, and A1C with our printable PDF or smart digital tracker.')

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        {{-- Navigation --}}
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
                <h1>Free Diabetes Log Book: Instant PDF Download</h1>
                
                <p class="lead">
                    Don't wait for mail. Download and print your log book instantly—100% free.
                </p>

                <p>
                    Whether you prefer the simplicity of pen and paper or the power of AI analytics, we have you covered. Choose the method that fits your lifestyle to spot patterns, lower your A1C, and take control of your metabolic health.
                </p>

                {{-- Primary CTA: The Digital App (The Upsell) --}}
                <div class="my-10 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900 rounded-2xl p-8 shadow-sm">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-1">
                            <h2 class="text-emerald-900 dark:text-emerald-100 mt-0! text-2xl!">Option 1: The Smart Digital Tracker (Recommended)</h2>
                            <p class="text-slate-600 dark:text-slate-300 mb-4!">
                                Don't just log numbers—<strong>solve them</strong>. Acara Plate's digital logbook automatically calculates your averages, estimates your A1C, and correlates your insulin doses with your food.
                            </p>
                            <ul class="list-none pl-0! space-y-2 text-sm text-emerald-800 dark:text-emerald-200 mb-6">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Auto-Calculate A1C & Averages
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    See Glucose Trends (Charts & Graphs)
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Secure Cloud Backup (Never lose data)
                                </li>
                            </ul>
                            <a href="/register" class="inline-flex items-center justify-center w-full md:w-auto px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold no-underline shadow-md hover:shadow-lg transition-all">
                                Start Tracking Free
                            </a>
                        </div>
                        {{-- todo: Add a small screenshot of your Dashboard here if available --}}
                    </div>
                </div>

                {{-- Secondary CTA: The Paper PDF (The Legacy SEO Catch) --}}
                <div class="border-t border-slate-200 dark:border-slate-800 pt-8">
                    <h2>Option 2: The Classic Printable Log Book</h2>
                    <p>
                        Sometimes you just want to write it down. Our free PDF template is designed by diabetics for clear, distraction-free logging.
                    </p>
                    
                    <div class="grid md:grid-cols-2 gap-8 my-8">
                        <div>
                            <h3 class="mt-0!">What's Inside:</h3>
                            <ul>
                                <li><strong>4-Point Daily Check:</strong> Breakfast, Lunch, Dinner, Bedtime.</li>
                                <li><strong>Notes Section:</strong> Record meds, carbs, and feelings.</li>
                                <li><strong>Doctor Ready:</strong> Clean layout for your endocrinologist reviews.</li>
                            </ul>
                        </div>
                        <div class="flex items-center justify-center bg-slate-50 dark:bg-slate-800 rounded-xl p-6">
                            <a
                                href="{{ route('diabetes-log-book') }}"
                                class="inline-flex items-center px-6 py-3 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors font-medium shadow-sm no-underline"
                            >
                                <svg class="size-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download PDF Template
                            </a>
                        </div>
                    </div>
                </div>

                <h3>Why Upgrade to Digital?</h3>
                <p>
                    Paper is great for recording history, but it can't help you predict the future. Here is why thousands of users are switching to the Acara Smart Tracker:
                </p>

                <div class="overflow-x-auto my-8">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="py-4 font-semibold">Feature</th>
                                <th class="py-4 font-semibold text-slate-500">Paper Log Book</th>
                                <th class="py-4 font-bold text-emerald-600">Acara App</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3">Logging Speed</td>
                                <td class="py-3 text-slate-500">Slow (Handwriting)</td>
                                <td class="py-3 font-medium">Fast (Tap & Go)</td>
                            </tr>
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3">Analysis</td>
                                <td class="py-3 text-slate-500">None (Raw Numbers)</td>
                                <td class="py-3 font-medium">Trends & A1C Prediction</td>
                            </tr>
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3">Food Correlation</td>
                                <td class="py-3 text-slate-500">Guesswork</td>
                                <td class="py-3 font-medium">Automatic Impact Scoring</td>
                            </tr>
                            <tr>
                                <td class="py-3">Backup</td>
                                <td class="py-3 text-slate-500">None (Don't lose it!)</td>
                                <td class="py-3 font-medium">Secure Cloud Sync</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-blue-50 dark:bg-blue-950/50 border-l-4 border-blue-500 p-6 my-8 rounded-r-lg">
                    <p class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        💡 Pro Tip: The Hybrid Method
                    </p>
                    <p class="text-blue-800 dark:text-blue-200 mb-0">
                        Many users print the PDF for their fridge as a backup, but use the App for daily logging to get the analytics. You can do both!
                    </p>
                </div>

                {{-- Important Disclaimer --}}
                <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-lg my-8">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-0">
                        <strong>Important:</strong> Whether you use paper or digital, consistent tracking is key. This tool does not replace medical advice. Always consult with your healthcare provider about your diabetes management plan.
                    </p>
                </div>

                <div class="not-prose mt-16 border-t border-slate-200 pt-12 dark:border-slate-700">
                    <h2 class="text-center text-2xl font-bold text-slate-900 dark:text-white mb-8">More Tools to Lower Your Spikes</h2>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <a href="{{ route('spike-calculator') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md hover:ring-emerald-500 dark:bg-slate-800 dark:ring-slate-700 dark:hover:ring-emerald-500">
                            <span class="mb-3 text-4xl">⚡️</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Spike Calculator</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Calculate how specific foods will impact your glucose based on your carb ratio.</p>
                        </a>
                        <a href="/" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md hover:ring-indigo-500 dark:bg-slate-800 dark:ring-slate-700 dark:hover:ring-indigo-500">
                            <span class="mb-3 text-4xl">🤖</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">AI Nutritionist</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Get a personalized 7-day meal plan optimized for diabetes management and gut health.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-footer />
</x-default-layout>