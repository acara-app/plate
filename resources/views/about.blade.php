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
                
                <h2>The Vision</h2>
                <p>
                    The goal is simple: help users to take control of their health through personalized nutrition and diabetes management. 
                </p>
                <p>
                    Acara Plate creates customized meal plans based on unique biometrics, dietary preferences, and health goals. Whether managing diabetes, working toward weight loss, or optimizing metabolic health, the platform simplifies meal planning with tailored recipes, nutritional insights, and glucose tracking capabilities.
                </p>
                <p>
                    What makes Acara Plate different? A focus on evidence-based nutrition combined with agentic intelligence. Powered by high-fidelity nutritional data and personalized algorithms, meal plans are generated to align with specific needs—including dietary restrictions, health conditions, and lifestyle factors. Every recommendation is designed to fit seamlessly into daily routines while supporting the long-term wellness journey.
                </p>

                <h2>How It Works</h2>
                <p>
                    The platform guides users through a comprehensive onboarding process to capture key information about age, weight, height, activity level, dietary preferences, and health conditions like Type 2 Diabetes or hypertension. 
                </p>
                <p>
                    AI processes this information to generate personalized meal plans complete with macro-balanced recipes, portion guidance, and automated grocery lists. Track progress through an integrated diabetes logbook, monitor glucose trends, and receive actionable insights to improve time-in-range.
                </p>

                <h2>Roadmap: Towards a Fully Agentic System</h2>
                <p>
                    Health should be personal, flexible, and evidence-based. We are moving beyond simple tracking toward a proactive, autonomous system that adapts to your life in real-time.
                </p>

                <h3>Personalization & Agentic Intelligence</h3>
                <p>
                    The journey toward a fully agentic system starts with deep personalization. Instead of static meal plans, Acara Plate is evolving into a proactive health partner that understands unique biometrics, real-time responses, and long-term goals. 
                </p>
                <p>
                    Today, the platform already supports a wide range of dietary patterns—from ketogenic and vegan to low-FODMAP and allergen-conscious eating. We are building the intelligence required to not just react to recorded data, but to anticipate needs and provide autonomous guidance.
                </p>
                <p>
                    Our recommendations are grounded in high-fidelity nutritional data and evidence-based insights. By moving beyond "one-size-fits-all" models, the system learns from every interaction, continuously refining its understanding of metabolic health to help users achieve optimal wellness through an increasingly autonomous and intelligent interface.
                </p>

                <h3>The Complete Agentic System</h3>
                <p>
                    Acara Plate isn’t just meal planning software—it’s a fully agentic nutrition companion that supports every step of the health journey.
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
                    <strong>Reminders & Support:</strong> Smart notifications help maintain meal timing, hydration, medication schedules, and glucose checks. The system learns routines and adapts reminders to fit the lifestyle, not the other way around.
                </p>
                <p>
                    <strong>Shopping & Logistics:</strong> Automated grocery lists organized by store layout, portion calculations adjusted for household size, and integration with nutrition tracking eliminate friction between planning and execution.
                </p>
                <p>
                    <strong>Continuous Learning:</strong> Every interaction teaches the system more about users' preferences, responses, and patterns. Over time, recommendations become increasingly personalized and effective.
                </p>
                
                <h2>Self-Hosting & Data Ownership</h2>
                <p>
                    As an open source project, Acara Plate offers complete control over data. Users can self-host the entire platform on their own infrastructure, ensuring sensitive health information never leaves their control.
                </p>
                <p>
                    Detailed documentation is provided for deployment using Laravel Forge, Ploi, or Laravel Cloud, with support for standard VPS providers like DigitalOcean, Hetzner, and AWS.
                </p>
                <p>
                    The self-hosting setup includes isolated database servers, automated backups with pgBackRest, and scalable architecture that grows with your needs. Since the application is built with Laravel 12, users benefit from enterprise-grade security, queue management for AI processing, and comprehensive logging. 
                </p>
                <p>
                    All providers (OpenAI, Anthropic, Gemini, DeepSeek, Groq, Mistral, XAI, or local Ollama) can be configured through environment variables, giving flexibility in model selection while keeping API keys secure.
                </p>
                <p>
                    By self-hosting, full ownership of nutrition data, meal plans, and health insights is maintained. No subscriptions, no data mining, no third-party access—just a powerful nutrition platform that works entirely on your terms.
                </p>

                <h2>Open Source & Transparent</h2>
                <p>
                    Acara Plate is proudly open source, built transparently with community input. Health technology should be accessible and community-driven, with full visibility into how the system generates recommendations. 
                </p>
                <p>
                    You can review the code, suggest improvements, and contribute to the project on <a href="https://github.com/acara-app/plate" target="_blank" rel="noopener">GitHub</a>.
                </p>
                <p>
                    Privacy and data security are paramount. All personal health data is processed securely and used solely for generating personalized nutrition plans. Data is never sold to third parties.
                </p>
                <p>
                    For complete details, please review the <a href="{{ route('privacy') }}">Privacy Policy</a> and <a href="{{ route('terms') }}">Terms of Service</a>.
                </p>

                <h2>Stay Updated</h2>
                <p>
                    Acara Plate is constantly improving with new features, better AI models, and enhanced user experience. Follow the progress on <a href="https://github.com/acara-app/plate" target="_blank" rel="noopener">GitHub</a> to see the latest updates, or reach out with feedback and suggestions.
                </p>

                <h2>Contact & Support</h2>
                <p>
                    Have questions about Acara Plate? Found a bug or have a feature request? Feedback is always welcome!
                </p>
                <p>
                    For general inquiries, support, or collaboration opportunities, reach out at <a href="mailto:team@acara.app">team@acara.app</a>.
                </p>
                <p>
                    For technical issues or bugs, please open an issue on the <a href="https://github.com/acara-app/plate/issues" target="_blank" rel="noopener">GitHub repository</a> where you can track feature requests and join discussions about the project's direction.
                </p>

                <p>
                    <strong>Acara</strong><br>
                    <em>Personalized Nutrition for Better Metabolic Health</em><br>
                    AI-generated meal plans, glucose tracking, and actionable insights to help manage diabetes, lose weight, or optimize wellness.<br>
                    <a href="mailto:team@acara.app">team@acara.app</a>
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>