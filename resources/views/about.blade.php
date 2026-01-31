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
                <h1>About Acara Plate</h1>
                
                <h2>Our Mission</h2>
                <p>
                    We have one mission: helping you take control of your health through personalized nutrition and diabetes management. 
                </p>
                <p>
                    Acara Plate creates customized meal plans based on your unique biometrics, dietary preferences, and health goals. Whether you're managing diabetes, working toward weight loss, or optimizing your metabolic health, we simplify meal planning with tailored recipes, nutritional insights, and glucose tracking capabilities.
                </p>
                <p>
                    What sets us apart? A focus on evidence-based nutrition combined with smart technology. Based on USDA-verified food data and personalized algorithms, we generate meal plans that align with your specific needs—including dietary restrictions, health conditions, and lifestyle factors. Every recommendation is designed to fit seamlessly into your daily routine while supporting your long-term wellness journey.
                </p>

                <h2>How It Works</h2>
                <p>
                    Our platform guides you through a comprehensive onboarding process where you provide key information about your age, weight, height, activity level, dietary preferences, and health conditions like Type 2 Diabetes or hypertension. 
                </p>
                <p>
                    AI processes this information to generate personalized meal plans complete with macro-balanced recipes, portion guidance, and automated grocery lists. Track progress through an integrated diabetes logbook, monitor glucose trends, and receive actionable insights to improve time-in-range.
                </p>

                                <h2>Our Holistic Approach to Health</h2>
                <p>
                    At Acara Plate, we believe health should be personal, flexible, and evidence-based. Consider your complete health profile, dietary preferences, and lifestyle to create meal plans that are both enjoyable and effective.
                </p>

                <h3>Nutrition & Diet Personalization</h3>
                <p>
                    Whether following a specific dietary pattern or managing multiple restrictions, the platform adapts to you—not the other way around. A wide range of dietary preferences are supported: vegan, vegetarian, ketogenic, paleo, Mediterranean, gluten-free, dairy-free, low-FODMAP, and allergen-conscious eating.
                </p>
                <p>
                    Every meal plan automatically excludes foods you dislike or cannot eat, while meeting caloric and macronutrient targets. For those managing diabetes, meals with appropriate glycemic impact and carbohydrate distribution are prioritized throughout the day.
                </p>
                <p>
                    Recommendations are grounded in USDA FoodData Central, providing accurate nutritional information for thousands of ingredients. One-size-fits-all diets don’t work—the platform learns from feedback and preferences to continuously improve recommendations that fit your lifestyle, cultural food preferences, and health goals.
                </p>

                <h3>The Complete Agentic System</h3>
                <p>
                    Acara Plate isn’t just meal planning software—it’s a fully agentic nutrition companion that supports every step of your health journey.
                </p>
                <p>
                    <strong>Planning:</strong> AI analyzes complete health profiles, goals, and preferences to generate personalized meal plans that adapt as needs change. Whether adjusting for weight loss plateaus, changing activity levels, or new health conditions, the system continuously optimizes recommendations.
                </p>
                <p>
                    <strong>Safety:</strong> Automated checks ensure meal plans respect dietary restrictions, allergies, and health conditions. For diabetes management, the system considers glycemic impact, carbohydrate distribution, and medication timing.
                </p>
                <p>
                    <strong>Wellness:</strong> Beyond nutrition, the platform tracks glucose trends, energy levels, and overall health markers. Insights connect what you eat with how you feel, helping understand unique responses to different foods and meal timing.
                </p>
                <p>
                    <strong>Reminders & Support:</strong> Smart notifications keep you on track with meal timing, hydration, medication schedules, and glucose checks. The system learns routines and adapts reminders to fit your lifestyle, not the other way around.
                </p>
                <p>
                    <strong>Shopping & Logistics:</strong> Automated grocery lists organized by store layout, portion calculations adjusted for household size, and integration with nutrition tracking eliminate friction between planning and execution.
                </p>
                <p>
                    <strong>Continuous Learning:</strong> Every interaction teaches the system more about your preferences, responses, and patterns. Over time, recommendations become increasingly personalized and effective.
                </p><h2>Self-Hosting & Data Ownership</h2>
                <p>
                    As an open source project, Acara Plate gives you complete control over your data. You can self-host the entire platform on your own infrastructure, ensuring your sensitive health information never leaves your control.
                </p>
                <p>
                    We provide detailed documentation for deployment using Laravel Forge, Ploi, or Laravel Cloud, with support for standard VPS providers like DigitalOcean, Hetzner, and AWS.
                </p>
                <p>
                    The self-hosting setup includes isolated database servers, automated backups with pgBackRest, and scalable architecture that grows with your needs. Since the application is built with Laravel 12, you benefit from enterprise-grade security, queue management for AI processing, and comprehensive logging. 
                </p>
                <p>
                    All providers (OpenAI, Anthropic, Gemini, DeepSeek, Groq, Mistral, XAI, or local Ollama) can be configured through environment variables, giving you flexibility in model selection while keeping API keys secure.
                </p>
                <p>
                    By self-hosting, you maintain full ownership of your nutrition data, meal plans, and health insights. No subscriptions, no data mining, no third-party access—just a powerful nutrition platform that works entirely on your terms.
                </p>

                <h2>Open Source & Transparent</h2>
                <p>
                    Acara Plate is proudly open source, built transparently with community input. We believe health technology should be accessible and community-driven, with full visibility into how the system generates recommendations. 
                </p>
                <p>
                    You can review our code, suggest improvements, and contribute to the project on <a href="https://github.com/acara-app/plate" target="_blank" rel="noopener">GitHub</a>.
                </p>
                <p>
                    Your privacy and data security are paramount. All personal health data is processed securely and used solely for generating personalized nutrition plans. We never sell your data to third parties.
                </p>
                <p>
                    For complete details, please review our <a href="{{ route('privacy') }}">Privacy Policy</a> and <a href="{{ route('terms') }}">Terms of Service</a>.
                </p>

                <h2>Stay Updated</h2>
                <p>
                    We're constantly improving Acara Plate with new features, better AI models, and enhanced user experience. Follow our progress on <a href="https://github.com/acara-app/plate" target="_blank" rel="noopener">GitHub</a> to see the latest updates, or reach out with feedback and suggestions.
                </p>

                <h2>Contact & Support</h2>
                <p>
                    Have questions about Acara Plate? Found a bug or have a feature request? We'd love to hear from you!
                </p>
                <p>
                    For general inquiries, support, or collaboration opportunities, reach out to us at <a href="mailto:team@acara.app">team@acara.app</a>.
                </p>
                <p>
                    For technical issues or bugs, please open an issue on our <a href="https://github.com/acara-app/plate/issues" target="_blank" rel="noopener">GitHub repository</a> where you can track feature requests and join discussions about the project's direction.
                </p>

                <p>
                    <strong>Acara</strong><br>
                    <em>Personalized Nutrition AI</em><br>
                    <a href="mailto:team@acara.app">team@acara.app</a>
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>