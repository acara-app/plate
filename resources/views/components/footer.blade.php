<footer class="border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-slate-950 w-full" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    <div class="mx-auto max-w-6xl py-12 px-6 lg:px-0">
        <div class="xl:grid xl:grid-cols-3 xl:gap-8">
            <div class="space-y-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white" title="Acara Plate - AI Nutritionist & Diabetes Management Home">
                    <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                    Acara Plate
                </a>
                <p class="text-sm leading-6 text-slate-600 dark:text-slate-400">
                    AI-powered nutrition and meal planning for Type 2 diabetes management.
                </p>
                <div class="flex space-x-6">
                    <a href="https://github.com/acara-app/plate" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                        <span class="sr-only">GitHub</span>
                        <x-icons.github class="h-6 w-6" />
                    </a>
                </div>
            </div>
            <div class="mt-16 grid grid-cols-2 gap-8 xl:col-span-2 xl:mt-0">
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-slate-900 dark:text-white">Free Diabetes Tools</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <a href="{{ route('tools.index') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium" title="View all free diabetes tools">All Tools</a>
                            </li>
                            <li>
                                <a href="{{ route('food.index') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Search for foods">Diabetic Food Database</a>
                            </li>
                            <li>
                                <a href="{{ route('spike-calculator') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Check glucose impact of foods">Spike Calculator</a>
                            </li>
                            <li>
                                <a href="{{ route('snap-to-track') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Analyze food photos with AI">Food Photo Analyzer</a>
                            </li>
                            <li>
                                <a href="{{ route('usda-servings-calculator') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="USDA 2025-2030 daily serving calculator">Daily Servings Calculator</a>
                            </li>
                            <li>
                                <a href="{{ route('diabetes-log-book-info') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Track your diabetes management">Diabetes Log Book</a>
                            </li>
                            <li>
                                <a href="{{ route('10-day-meal-plan') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Free 10-day diabetes meal plan">10-Day Meal Plan</a>
                            </li>
                            <li>
                                <a href="{{ route('install-app') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" title="Install Acara Plate as an App">Install App</a>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-10 md:mt-0">
                                                <h3 class="text-sm font-semibold leading-6 text-slate-900 dark:text-white">Support</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <a href="{{ route('support') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">Help & Support</a>
                            </li>
                            <li>
                                <a href="{{ route('about') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">About</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-slate-900 dark:text-white">Legal</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <a href="{{ route('privacy') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">Privacy Policy</a>
                            </li>
                            <li>
                                <a href="{{ route('terms') }}" class="text-sm leading-6 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">Terms of Service</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-16 border-t border-gray-900/10 pt-8 sm:mt-20 lg:mt-24 dark:border-white/10">
            <small class="block text-xs leading-5 text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Disclaimer:</span> Acara Plate is an AI-powered tool for informational purposes only and does not provide medical advice. Always consult a healthcare professional for medical concerns.
            </small>
            <small class="block mt-4 text-xs leading-5 text-slate-500 dark:text-slate-400">&copy; {{ date('Y') }} Acara Plate. All rights reserved.</small>
        </div>
    </div>
</footer>
