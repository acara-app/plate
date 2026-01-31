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
                <h1>Help & Support</h1>
                
                <p>
                    Building Acara Plate, I understand how frustrating it can be when things don't work as expected. Whether you're managing diabetes, trying to lose weight, or optimizing your nutrition, you deserve reliable support.
                </p>

                <h2>How to Get Help</h2>
                <p>
                    Choose the best contact method for your situation:
                </p>

                <h3>📧 Email Support (Recommended)</h3>
                <p>
                    <strong>team@acara.app</strong>
                </p>
                <ul>
                    <li>Response time: 24-48 hours</li>
                    <li>Best for: account problems, billing questions, personal health data concerns</li>
                    <li>I respond personally to every email</li>
                </ul>

                <h3>🐛 GitHub Issues</h3>
                <p>
                    <strong><a href="https://github.com/acara-app/plate/issues" target="_blank" rel="noopener">Report on GitHub</a></strong>
                </p>
                <ul>
                    <li>Best for: technical bugs, error messages, feature requests</li>
                    <li>Transparent tracking: see what others have reported</li>
                    <li>Helps improve the platform for everyone</li>
                </ul>

                <h2>Frequently Asked Questions</h2>

                <h3>How is my health data protected?</h3>
                <p>
                    Your health data is stored securely and never sold to third parties. As an open source project, you can review exactly how data is handled in the codebase. Your privacy is paramount—see our <a href="{{ route('privacy') }}">Privacy Policy</a> for complete details.
                </p>

                <h3>How does AI meal planning work?</h3>
                <p>
                    Acara Plate uses AI to analyze your complete health profile (age, weight, height, activity level, dietary preferences, health conditions) and generates personalized meal plans. The system considers USDA-verified food data, your restrictions, and continuously learns from your feedback to improve recommendations.
                </p>

                <h3>Can I use this with my diabetes medication?</h3>
                <p>
                    Acara Plate is designed to complement your diabetes management plan, not replace it. Always consult your healthcare provider before making changes to your diet or medication. The platform helps you track glucose trends and understand how different foods affect your levels.
                </p>

                <h3>What if I have multiple dietary restrictions?</h3>
                <p>
                    The platform supports multiple restrictions simultaneously (vegan, gluten-free, allergies, etc.). Simply specify all your restrictions during onboarding, and the AI will generate meal plans that respect all of them while meeting your nutritional needs.
                </p>

                <h3>How accurate is the nutrition data?</h3>
                <p>
                    All nutrition data comes from USDA FoodData Central, providing accurate nutritional information for thousands of ingredients. The platform uses verified, science-based data to ensure reliable nutrition tracking and meal planning.
                </p>

                <h3>Can I export my health data?</h3>
                <p>
                    Yes! As an open source project, Acara Plate gives you complete control over your data. You can self-host the entire platform on your own infrastructure, ensuring everything stays on your servers. See the self-hosting instructions in our <a href="https://github.com/acara-app/plate" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">GitHub README</a> for full data portability.
                </p>

                <h3>What should I do if the app isn't working?</h3>
                <p>
                    First, try clearing your browser cache and refreshing the page. If problems persist:
                    1. Check our <a href="https://github.com/acara-app/plate/issues">GitHub Issues</a> to see if others have reported the same problem
                    2. Report the issue with details about what happened and your browser/device
                    3. Try the <a href="{{ route('install-app') }}">installable app</a> version for a more stable experience
                </p>

                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <h3>About the Developer</h3>
                    <p>
                        Acara Plate is an open source project built transparently to help people manage diabetes and nutrition challenges through evidence-based tools. As an open source project, the entire codebase is transparent and community-driven.
                    </p>
                    <p>
                        <strong>Why I built this:</strong> After seeing how difficult it is to find nutrition tools that truly understand the complexity of diabetes management, I created Acara Plate to help others take control of their health through personalized, evidence-based nutrition.
                    </p>
                    <p>
                        <a href="https://github.com/acara-app/plate" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">View the code on GitHub →</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>