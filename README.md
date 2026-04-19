![Acara Plate - Health AI Agent Platform](public/banner-acara-plate.webp)

# Acara Plate: Open-Source Health AI Agent Platform

[![License: O'Saasy](https://img.shields.io/badge/License-O'Saasy-blue.svg)](LICENSE)
[![Tests](https://github.com/acara-app/plate/actions/workflows/tests.yml/badge.svg)](https://github.com/acara-app/plate/actions/workflows/tests.yml)
[![Why PHP](https://img.shields.io/badge/Why_PHP-in_2026-7A86E8?style=flat-square&labelColor=18181b)](https://whyphp.dev)

**[Try Acara Plate](https://plate.acara.app/)** | **[Read What Acara Is](https://plate.acara.app/post/what-is-acara)** | **[Get Acara Health Sync on the App Store](https://apps.apple.com/us/app/acara-health-sync/id6761504525)**

Acara Plate is a self-hostable health AI agent platform for asking health questions, connecting personal health data, tracking daily signals, and turning that context into practical guidance. Nutrition, diabetes support, meal planning, coaching, scanning, the Telegram chat interface, and Apple Health sync are features inside the broader Acara health AI system.

> [!IMPORTANT]
> Acara Plate is informational and educational software, not a medical device. It does not diagnose, treat, or replace a physician, registered dietitian, diabetes educator, or emergency care. Verify AI-generated nutrition guidance before using it for health decisions.

## Why Acara Exists

Billions of people still struggle to see a doctor, ask health questions, or get clear science-based answers when they need them. Many end up with search results full of ads, misinformation, or paywalls instead of useful guidance.

Acara's mission is to make reliable health AI easier to reach in everyday life.

Acara Plate is the core web platform for that mission. It brings questions, health data, progress, and day-to-day guidance into one place so the AI agent can respond with more context than a generic chatbot or single-purpose tracker.

Developers get the same idea in code form: a Laravel application that shows how to build AI agents, health data ingestion, safety-aware prompts, PWA delivery, a Telegram-based chat interface, Apple Health sync, image analysis, and structured health workflows around a privacy-first product.

## What the AI Agent Does

- **Ask AI:** Ask about symptoms, conditions, medications, vaccines, nutrition, mental health, habits, and next steps in plain language.
- **Use personal context:** Let the agent consider health data you choose to share, including glucose, sleep, activity, weight, blood pressure, medications, food, and lifestyle notes.
- **Guide healthier routines:** Get support for sleep, stress, hydration, movement, and daily habits through the [AI Health Coach](https://plate.acara.app/ai-health-coach).
- **Support nutrition decisions:** Use nutrition and meal-planning features such as the [AI Nutritionist](https://plate.acara.app/ai-nutritionist), [AI Meal Planner](https://plate.acara.app/meal-planner), grocery lists, recipes, and glucose-aware food guidance.
- **Connect Apple Health:** Use [Acara Health Sync](https://plate.acara.app/tools/health-sync) to send Apple Health data from iPhone to Plate. The iOS app is available on the [App Store](https://apps.apple.com/us/app/acara-health-sync/id6761504525).
- **Use Telegram as a chat interface:** Message the supported Telegram bot to ask the same health, nutrition, meal-planning, and tracking questions you ask in the web app. It can also save health entries in natural language through the [Telegram health logging tool](https://plate.acara.app/tools/telegram-health-logging), for example "fasting glucose 102" or "took 10 units insulin".
- **Scan and understand food:** Use public [free tools](https://plate.acara.app/tools) and image analysis workflows to inspect meals, estimate nutrition, and understand food choices faster.
- **Stay safety-aware:** When a message looks urgent, the agent is instructed to point users toward the relevant local emergency number instead of treating the chat as medical care.
- **Work across languages:** Acara is designed for multilingual use.
- **Keep data in your control:** Health data connections start with user consent, and self-hosting gives developers and families more control over where sensitive data lives.

## Ecosystem

| Project               | Purpose                                                                                                                               |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| **Acara Plate**       | The open-source, self-hostable Laravel web app, PWA, health AI agent, health data platform, and API backend.                          |
| **Acara Health Sync** | Native iOS companion app that reads Apple Health with permission and sends encrypted health data directly to a user's Plate instance. |
| **Acara Core**        | Premium Acara Cloud and private modules, including long-running semantic memory for preferences, goals, relationships, and context. It is not required for community Plate installs. |

## Product Links

Use these if you want to understand the public product before reading code:

- [Acara Plate home](https://plate.acara.app/)
- [What Is Acara Health AI?](https://plate.acara.app/post/what-is-acara)
- [AI Nutritionist](https://plate.acara.app/ai-nutritionist)
- [AI Health Coach](https://plate.acara.app/ai-health-coach)
- [AI Meal Planner](https://plate.acara.app/meal-planner)
- [Acara Health Sync](https://plate.acara.app/tools/health-sync)
- [Telegram Health Logging](https://plate.acara.app/tools/telegram-health-logging)
- [Free Tools](https://plate.acara.app/tools)
- [Food Database](https://plate.acara.app/food)

## For Developers

Acara Plate is a Laravel 13 application with a React/Inertia frontend and a comprehensive health AI agent built on Laravel's first-party AI SDK.

Core stack:

- PHP 8.4 or 8.5
- Laravel 13
- Laravel AI SDK
- Laravel Sanctum, Fortify, Cashier, Socialite, Wayfinder, and Livewire
- Inertia React 19
- Tailwind CSS 4
- PostgreSQL for production-like installs
- Pest, PHPStan, Pint, Rector, TypeScript, Prettier, and oxlint for quality checks

## Quick Setup

```bash
git clone https://github.com/acara-app/plate.git
cd plate
composer setup
```

`composer setup` installs PHP and JavaScript dependencies, creates `.env` when missing, generates the app key, runs migrations, installs Bun packages, and builds frontend assets.

Configure only the services you plan to use:

```env
APP_URL=http://localhost

# Choose the AI providers you want to enable.
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=

# Optional Telegram integration.
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_DOMAIN="${APP_URL}"

# Optional App Store override for local/private deployments.
HEALTH_SYNC_APP_STORE_URL=https://apps.apple.com/us/app/acara-health-sync/id6761504525
```

Run the development processes:

```bash
composer run dev
```

Run the project checks:

```bash
composer test
```

Useful targeted commands:

```bash
composer test:unit
composer test:types
composer test:lint
composer lint
```

## Community and Premium Builds

The public repository should stay community-safe:

- Do not require `acara-app/plate-core` for `main`.
- Do not import private package namespaces in public app code.
- Keep public extension contracts and null implementations in this repository so the app can boot without premium modules.
- Use the private Plate Core package only in Acara private or Cloud deployments.

If you are working on the public app, install from `main` and treat premium behavior as optional integration surface, not a required dependency.

## Health Sync Architecture

Acara Health Sync connects iPhone health data to Plate because web apps cannot read HealthKit directly.

The pairing flow is:

1. A user opens Plate and generates a Mobile Sync pairing token.
2. The user installs Acara Health Sync from the App Store.
3. The iOS app pairs with the user's Plate instance using the token.
4. The app stores credentials in the iOS Keychain.
5. Health data is encrypted on device and sent to Plate through the sync API.
6. Plate stores the readings and makes them available to dashboards and AI agent context.

The public Plate app owns the API and storage side of this flow. The iOS source repository is not public yet.

## Telegram Chat Interface

Telegram support lets users interact with Acara from Telegram instead of opening the web app or PWA. Users can ask Acara the same health, nutrition, meal-planning, and tracking questions they ask in the web app, and the bot can parse natural language entries for glucose, food, carbs, insulin, medication, vitals, and exercise through the [Telegram health logging tool](https://plate.acara.app/tools/telegram-health-logging), then confirm before saving.

For local webhook testing:

```bash
brew install ngrok
ngrok http https://plate.test
php artisan telegraph:new-bot
php artisan telegraph:set-webhook
```

## Data and Health Context

Acara Plate uses structured profile data, health entries, conversation context, food references, and user-approved device data to generate more relevant AI guidance. Food data is designed around USDA FoodData Central imports and application-specific glycemic context.

## Contributing

Contributions are welcome for the public Plate app. Start with the [contributing guide](CONTRIBUTING.md), follow the [code of conduct](CODE_OF_CONDUCT.md), and run the relevant tests before opening a pull request.

Good first areas to inspect:

- AI agent tools and prompts
- health data context and safety behavior
- glucose and health dashboards
- Apple Health sync API handling
- Telegram chat interface
- meal plan generation
- image-based food analysis
- translations and accessibility
- public nutrition tools

## License

Acara Plate is released under the [O'Saasy License](LICENSE). It supports source review, modification, and self-hosting, with restrictions on offering the software as a competing hosted SaaS product.

## Medical Disclaimer

Acara Plate provides AI-generated nutrition, wellness, and tracking support for informational and educational purposes only.

It is not professional medical advice, diagnosis, treatment, or a substitute for care from a licensed clinician. People with diabetes, prediabetes, pregnancy, eating disorders, kidney disease, cardiovascular conditions, medication changes, or other health concerns should consult a qualified healthcare professional before relying on any diet, medication, or glucose-management recommendation.

AI systems can make mistakes. Verify allergens, ingredients, medication details, glucose units, serving sizes, and nutrition values independently. In an emergency, contact local emergency services immediately.
