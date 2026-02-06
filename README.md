![Acara Plate - Personalized Nutrition AI](public/banner-acara-plate.webp)

# Acara Plate - Personalized Nutrition AI Agent

[![License: O'Saasy](https://img.shields.io/badge/License-O'Saasy-blue.svg)](LICENSE)
[![Tests](https://github.com/acara-app/plate/actions/workflows/tests.yml/badge.svg)](https://github.com/acara-app/plate/actions/workflows/tests.yml)
[![Why PHP](https://img.shields.io/badge/Why_PHP-in_2026-7A86E8?style=flat-square&labelColor=18181b)](https://whyphp.dev)

**[🚀 Live Demo](https://plate.acara.app)** — Try Acara Plate now without installation

Acara Plate is an AI-powered personalized nutrition and meal planning platform that creates customized meal plans based on individual user data such as age, weight, height, dietary preferences, and health goals. The platform simplifies meal planning by providing users with tailored recipes, nutritional information, and glucose tracking capabilities that align with their unique needs and lifestyle.

**Multi-Language Support:** Acara Plate is fully internationalized, making personalized nutrition accessible to users worldwide in their preferred language.

> [!IMPORTANT]
> **Disclaimer:** Acara Plate is an AI-powered tool for informational purposes only. It is not a substitute for professional medical advice, diagnosis, or treatment. See the [Medical Disclaimer](#medical-disclaimer) below.

## Table of Contents

- [Acara Plate - Personalized Nutrition AI Agent](#acara-plate---personalized-nutrition-ai-agent)
    - [Table of Contents](#table-of-contents)
    - [Overview](#overview)
    - [Product Capabilities](#product-capabilities)
        - [Personalization Inputs](#personalization-inputs)
        - [Generated Outputs](#generated-outputs)
        - [User Journey Highlights](#user-journey-highlights)
    - [Getting Started](#getting-started)
        - [Prerequisites](#prerequisites)
        - [Project Setup](#project-setup)
        - [Environment Configuration](#environment-configuration)
        - [Running the Development Server](#running-the-development-server)
        - [Testing \& Code Quality](#testing--code-quality)
    - [Data Initialization](#data-initialization)
        - [Database Seeding](#database-seeding)
        - [USDA Food Database Import](#usda-food-database-import)
    - [Deployment](#deployment)
        - [Self-Hosting Options](#self-hosting-options)
        - [Production Environment](#production-environment)
        - [Future Enhancements](#future-enhancements)
    - [Accessing Acara Plate](#accessing-acara-plate)
        - [Progressive Web App](#progressive-web-app)
    - [Contributing](#contributing)
    - [Code of Conduct](#code-of-conduct)
    - [License](#license)
- [Medical Disclaimer](#medical-disclaimer)

## Overview

Acara Plate is a Laravel 12 application that pairs Inertia (React) with Tailwind CSS to deliver a seamless AI-assisted meal planning experience. Powered by PrismPHP, it generates seven-day meal plans that adapt to each user's biometric data, preferences, and goals while tracking key wellness metrics such as glucose readings.

**Internationalization:** The application features comprehensive multi-language support with translations across all user-facing interfaces, including React components (via react-i18next), Laravel Blade templates, and email notifications.

## Product Capabilities

### Personalization Inputs

- **Biometrics:** Age, sex, height, weight, BMI, BMR, and TDEE calculations.
- **Goals:** Weight loss, muscle gain, maintenance, metabolic health, endurance, flexibility.
- **Lifestyle:** Activity level, occupation, and sleep patterns.
- **Preferences:** Vegan, vegetarian, keto, paleo, gluten-free, lactose-free, allergen exclusions, and dislikes.
- **Health Conditions:** Type 2 Diabetes, Pre-diabetes, Hypertension, Heart Disease, and other nutrition-sensitive conditions.

### Generated Outputs

- **Smart Meal Planning:**
    - Calorie targets strictly aligned with user goals.
    - Precise Macronutrient distribution (protein, carbs, fat).
    - Meal-by-meal recipes with quantities, portions, and prep guidance.
    - Grocery list generation (USDA-verified) with macro visualizations.
    - Printable meal plans with semantic HTML and PDF export.
- **Diabetes Management Logbook:**
    - **Glucose:** Tracking with context (fasting, pre/post-meal) and trend analytics.
    - **Insulin:** Logging for units and types (Bolus/Basal) to correlate doses with glucose outcomes.
    - **Carbs & Food:** Manual carbohydrate logging to track real-world intake vs. planned goals.
    - **Meds & Vitals:** Tracking for medication adherence, blood pressure, weight, and A1C.
    - **Exercise:** Activity logging to monitor the impact of movement on blood sugar levels.
- **Analytics & Insights:**
    - "Time-in-Range" visualization and glucose variability trends.
    - Automated analysis notifications with actionable recommendations via email.
- **Internationalization:**
    - Full multi-language support with extensible translation framework.
    - Seamless language switching across all interfaces.
    - Localized email notifications and printable documents.
    - Easy to add new languages via translation files.

### How Users Experience Acara Plate

**1. Tell Us About Yourself**  
Answer a few questions about your body, goals, and lifestyle. Whether you're managing Type 2 diabetes, trying to lose weight, or just eating healthier — Acara Plate builds your profile in under 3 minutes.

**2. Get Your Personal Meal Plan**  
The AI creates a complete 7-day meal plan tailored to your calorie targets, macro needs, and food preferences. Each recipe includes portions, prep steps, and nutrition facts. No more guessing what to eat.

**3. Shop with Confidence**  
Generate a consolidated grocery list organized by store section. Everything you need for the week, no wasted ingredients, no impulse buys.

**4. Track What Matters**  
Log your glucose readings, meals, insulin, and activity in one place. Spot patterns — like how that afternoon coffee affects your numbers or which workouts keep you stable.

**5. Ask Anything, Anytime**  
Stuck at a restaurant? Wondering if a food will spike you? Chat with the AI Nutritionist for instant, personalized guidance based on your actual data.

### AI Nutritionist

Your personal AI-powered nutrition assistant, available 24/7:

- **Conversational Guidance:** Ask anything naturally — "What should I order at Chipotle?" or "Will this spike my blood sugar?"
- **Personalized Advice:** Context-aware recommendations based on your profile, goals, and glucose patterns
- **Restaurant Help:** Navigate any menu with confidence, from fast food to fine dining
- **Meal Planning:** Get day-of suggestions or full week plans based on your constraints
- **Glucose Predictions:** Understand how foods will affect your blood sugar before you eat

[Learn more about AI Nutritionist →](https://plate.acara.app/ai-nutritionist)

### Food Database

USDA-verified nutrition information at your fingertips:

- **310+ Foods:** Comprehensive database with glycemic index, glycemic load, and full nutrition facts
- **Diabetes-Focused:** Each food includes safety assessments and glucose impact predictions
- **Searchable:** Filter by category, GI impact, or search by name
- **Free & Open:** Browse the full database without signup at [plate.acara.app/food](https://plate.acara.app/food)

### Free Tools

No registration required — try these instantly:

- **Spike Calculator:** Check if any food will spike your blood sugar. Just type a food name and get instant glucose impact predictions.
  → [Try Spike Calculator](https://plate.acara.app/tools/spike-calculator)

- **Snap to Track:** Take a photo of your meal and get instant nutrition facts. AI-powered food recognition with macro breakdown.
  → [Try Snap to Track](https://plate.acara.app/tools/snap-to-track)

## Getting Started

### Prerequisites

This application is built with:

- **PHP 8.4**
- **Composer 2** — PHP dependency manager
- **Node.js 20+**
- **Laravel 12** — backend API and frontend delivery
- **[Laravel AI SDK](https://github.com/laravel/ai-sdk)** — AI agents, tools, and streaming
- **[PrismPHP](https://github.com/prism-php/prism)** — unified LLM interface for multiple AI providers
- **[Workflow](https://github.com/durable-workflow/workflow)** — durable workflow orchestration for meal plan generation
- **React 19** — frontend UI layer
- **Inertia.js** — bridges Laravel and React
- **Tailwind CSS** — utility-first styling
- **PostgreSQL 17+** (pgvector recommended for advanced features)

### Project Setup

```bash
git clone https://github.com/acara-app/plate.git
cd plate
git checkout -b feat/your-feature # or fix/your-fix
```

Create a feature branch instead of committing directly to `main`, then install and bootstrap dependencies:

```bash
composer setup
```

`composer setup` runs Composer and NPM installs, copies `.env.example`, generates the app key, and executes migrations.

### Environment Configuration

Configure the credentials you need in `.env`. Only the providers you enable in code require keys.

```bash
# Optional AI Provider API Keys (choose any subset)
OPENAI_API_KEY=your_openai_key
ANTHROPIC_API_KEY=your_anthropic_key
GEMINI_API_KEY=your_gemini_key
DEEPSEEK_API_KEY=your_deepseek_key
GROQ_API_KEY=your_groq_key
MISTRAL_API_KEY=your_mistral_key
XAI_API_KEY=your_xai_key
OLLAMA_URL=http://localhost:11434 # if using local Ollama

# Optional OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

### Running the Development Server

```bash
composer run dev
```

Use `npm run build` and your Herd `.test` domain when validating PWA installability. Clear site data if the service worker appears stale.

### Testing & Code Quality

Run the full QA suite:

```bash
composer test
```

Targeted commands are also available:

```bash
composer test:unit         # Unit & feature tests (100% coverage enforced)
composer test:type-coverage
composer test:lint         # Pint, Rector, ESLint, Prettier
composer test:types        # PHPStan + TypeScript
composer lint              # Auto-fix styling issues
```

## Data Initialization

### Database Seeding

```bash
php artisan db:seed --class=GoalSeeder
php artisan db:seed --class=LifestyleSeeder
php artisan db:seed --class=DietaryPreferenceSeeder
php artisan db:seed --class=HealthConditionSeeder
```

### USDA Food Database Import

Acara Plate relies on USDA FoodData Central for accurate nutrition data:

1. Download **Foundation Foods** and **SR Legacy Foods** (JSON) from [FoodData Central](https://fdc.nal.usda.gov/download-datasets).
2. Place the files in `storage/sources/`.
3. Import using the provided Artisan commands:

```bash
php artisan import:usda-food-foundation-data
php artisan import:usda-sr-legacy-food-data

# Use custom paths if needed
php artisan import:usda-food-foundation-data --path=/path/to/foundation.json
php artisan import:usda-sr-legacy-food-data --path=/path/to/legacy.json
```

**Performance & Indexing**

- Streaming import efficiently handles large JSON payloads.
- Foundation Foods (~1,200 entries) completes in ~2-5 seconds; SR Legacy (>8,000) in ~10-30 seconds.
- Operations run within database transactions and surface progress in real time.
- Full-text indexes on the `description` column accelerate search (created on MySQL/PostgreSQL, skipped on SQLite).

## Deployment

### Self-Hosting Options

- **Laravel Forge:** Automated provisioning for VPS providers (DigitalOcean, Linode, Vultr, AWS).
- **Ploi:** Laravel Forge–style GUI for provisioning, deployments, cron management, and queue supervision.
- **Laravel Cloud:** Fully managed Laravel platform with zero server maintenance.

### Production Environment

The live deployment is hosted on [Hetzner](https://www.hetzner.com/) with [Ploi](https://ploi.io/) coordinating releases. This setup serves as a practical template for similar self-managed installations. The current server runs Ubuntu 22.04 LTS with 2 vCPUs, 2 GB RAM, and 50 GB SSD storage.

- **Database:** Dedicated PostgreSQL VM isolated from the application server
- **Backups:** [pgBackRest](https://pgbackrest.org/) provides automated, incremental backups

### Future Enhancements

- IndexedDB caching for limited offline PWA usage (recipes, recent plans)
- Parallelized queue workers for faster meal plan generation

## Accessing Acara Plate

The application is available as a regular responsive web app—open your configured domain in any modern browser to use it immediately. Installing the PWA is optional and simply delivers an app-like shell around the same experience.

### Progressive Web App

Acara Plate ships as an installable PWA for mobile and desktop:

- **Capabilities:** Home screen install, standalone window, responsive layout
- **Current Limitation:** Offline mode is not yet available; an internet connection is required

**Installation**

- **iOS/iPadOS (Safari):** Share → Add to Home Screen
- **Android (Chrome):** Browser menu → Add to Home screen
- **Desktop (Chrome/Edge):** Click the install icon in the address bar or choose Install from the menu

**Updates**

- A new deployment becomes active after the service worker installs and the app performs a fresh reload
- If an update appears stuck, complete a hard refresh or clear storage for the domain

## Adding New Translations

Acara Plate welcomes translation contributions! The application uses a dual translation system:

**Backend (Laravel):**

- Translation files are located in `lang/{locale}/` (e.g., `lang/fr/`, `lang/es/`)
- Copy an existing locale directory (e.g., `lang/en/`) and translate the PHP array values
- Key files: `common.php`, `auth.php`, `validation.php`, `passwords.php`, `pagination.php`

**Frontend (React):**

- Translations are loaded from Laravel backend via Inertia.js
- React components use `react-i18next` with the `useTranslation()` hook
- No separate frontend translation files needed—all translations come from Laravel

**To add a new language:**

1. Create a new directory in `lang/` with the locale code (e.g., `lang/es/` for Spanish)
2. Copy all PHP files from `lang/en/` to your new locale directory
3. Translate the array values while keeping the keys unchanged
4. Test your translations by switching the app locale

**Usage in React Components:**

```tsx
import { useTranslation } from 'react-i18next';

export default function MyComponent() {
    const { t } = useTranslation('common'); // Specify namespace
    return <h1>{t('welcome')}</h1>;
}
```

**Usage in Laravel:**

```php
// In controllers or Blade templates
__('common.welcome');
__('auth.throttle', ['seconds' => 60]); // With parameters
```

**Translation Namespaces:**

- `auth` - Authentication-related strings
- `common` - Common UI elements and general translations
- `validation` - Form validation messages
- `passwords` - Password reset and management
- `pagination` - Pagination controls

Contributions for new languages are highly encouraged! See the [Contributing Guide](CONTRIBUTING.md) for submission guidelines.

## Contributing

Contributions are welcome! Review the [Contributing Guide](CONTRIBUTING.md) for workflows, coding standards, and issue triage details.

## Code of Conduct

Please read the [Code of Conduct](CODE_OF_CONDUCT.md) before participating in the community.

## License

Acara Plate is released under the [O'Saasy License](LICENSE).

# Medical Disclaimer

Acara Plate is an open-source project designed for informational and educational purposes only.

**Not Medical Advice:** This software is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition, dietary changes, or blood glucose management.

**AI Limitations:** Meal plans and nutritional data are generated by large language models (OpenAI, Anthropic Claude, Google Gemini, DeepSeek, Groq, Mistral, XAI, etc.) via PrismPHP. While accuracy is prioritized, LLMs can misstate allergens, ingredients, or macro values. Verify critical information independently.

**No Liability:** Authors and contributors are not liable for adverse effects, health complications, or damages arising from use of the software or reliance on its information.

**Emergency:** If you think you may have a medical emergency, contact your physician or emergency services immediately.

By using this software, you acknowledge you have read this disclaimer and agree to use the application at your own risk.
