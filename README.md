# Acara Plate - AI Nutrition for Diabetes

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Tests](https://github.com/acara-app/plate/actions/workflows/tests.yml/badge.svg)](https://github.com/acara-app/plate/actions/workflows/tests.yml)

Acara Plate is an AI-powered personalized nutrition and meal planning platform that creates customized meal plans based on individual user data such as age, weight, height, dietary preferences, and health goals. The platform simplifies meal planning by providing users with tailored recipes, nutritional information, and glucose tracking capabilities that align with their unique needs and lifestyle.

> [!IMPORTANT]
> **Disclaimer:** Acara Plate is an AI-powered tool for informational purposes only. It is not a substitute for professional medical advice, diagnosis, or treatment. See the [Medical Disclaimer](#medical-disclaimer) below.

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Code of Conduct

Please review our [Code of Conduct](CODE_OF_CONDUCT.md) before participating.

## About

Acara Plate is AI-Powered through PrismPHP to generate comprehensive 7-day meal plans that consider:

- **Biometric Data**: Age, height, weight, sex, BMI, BMR, and TDEE calculations
- **Health Goals**: Weight loss, muscle gain, weight maintenance, health condition management, endurance improvement, flexibility enhancement
- **Lifestyle Factors**: Activity level (sedentary to extremely active), occupation, sleep patterns
- **Dietary Preferences**: Vegan, vegetarian, keto, paleo, gluten-free, lactose-free, allergen restrictions, and food dislikes
- **Health Conditions**: Diabetes, hypertension, heart disease, and other conditions with nutritional impacts

The platform provides detailed meal plans with:
- Daily calorie targets aligned with user goals
- Macronutrient ratios (protein, carbs, fat)
- Complete recipes with preparation instructions
- Ingredient lists with quantities
- Portion sizes and preparation times
- Nutritional information per meal

## Installation

Acara Plate is a regular Laravel application; it's built on top of Laravel 12 and uses Inertia (React) / Tailwind CSS for the frontend. If you are familiar with Laravel, you should feel right at home.

In terms of local development, you can use the following requirements:

- **PHP 8.4+**
- **Node.js 20+**
- **SQLite** or **PostgreSQL 17+** (with pgvector extension)

If you have these requirements, you can start by cloning the repository and installing the dependencies:

```bash
git clone https://github.com/acara-app/plate.git

cd plate

git checkout -b feat/your-feature # or fix/your-fix
```

Don't push directly to the main branch. Instead, create a new branch and push it to your branch.

Next, install the dependencies using Composer and NPM:

```bash
composer setup
```

> **Note**: The `composer setup` command is a shortcut that runs `composer install`, `npm install`, copies `.env.example` to `.env`, generates the app key, and runs migrations.

### Configure Environment

After setup, you may need to configure additional environment variables in your `.env` file:

```bash
# Required API Keys
GOOGLE_GEMINI_API_KEY=your_gemini_key

# Optional: Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

### Database Seeding

Optionally, you can seed the database with initial data:

```bash
php artisan db:seed --class=GoalSeeder
php artisan db:seed --class=LifestyleSeeder
php artisan db:seed --class=DietaryPreferenceSeeder
php artisan db:seed --class=HealthConditionSeeder
```

## Development

### Running the Development Server

```bash
composer run dev
```

## Testing & Code Quality

Acara Plate maintains strict code quality standards to ensure reliability and maintainability. We use **Pest** as our testing framework, enforcing **100% test coverage** and **100% type coverage**.

For static analysis, we rely on **PHPStan** and TypeScript checks. To keep our codebase clean and modern, we utilize **Laravel Pint** for styling and **Rector** for automated refactoring.

### Running Tests

You can run the full quality assurance suite with a single command:

```bash
composer test
```

### Individual Tools

You can also run specific tools individually:

```bash
# Run unit and feature tests (requires 100% coverage)
composer test:unit

# Check type coverage (requires 100%)
composer test:type-coverage

# Run code style checks (Pint, Rector, ESLint, Prettier)
composer test:lint

# Run static analysis (PHPStan, TypeScript)
composer test:types
```

### Fixing Code Style

To automatically fix code style issues:

```bash
composer lint
```


## Key Features

### User Journey
1. **Onboarding Questionnaire**
   - Biometric information collection
   - Goal selection
   - Lifestyle assessment
   - Dietary preference selection
   - Health condition documentation

2. **AI-Powered Meal Plan Generation**
   - Personalized 7-day meal plans
   - Weekly plan generation with queue processing
   - Real-time job tracking with progress updates
   - Structured data generation using PrismPHP

3. **Meal Plan Management**
   - View daily meals with nutritional breakdown
   - Day-by-day navigation
   - Macro bar visualizations
   - Detailed meal cards with preparation instructions
   - Grocery list generation capability

4. **Glucose Tracking**
   - Log blood glucose readings with timestamps
   - Track reading types (Fasting, Before Meal, Post Meal, Random)
   - Comprehensive analytics dashboard with visualizations
   - Time-period filtering (7, 30, 90 days)
   - Statistics: average, highest, lowest glucose levels
   - Time-in-range metrics (70-140 mg/dL target)
   - Interactive line chart with color-coded zones
   - Reading management (create, edit, delete)

# Medical Disclaimer

Acara Plate is an open-source project designed for informational and educational purposes only.

**Not Medical Advice:** This software is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition, dietary changes, or blood glucose management.

**AI Limitations:** The meal plans and nutritional data are generated by Artificial Intelligence (Google Gemini). While we strive for accuracy, AI models can occasionally hallucinate or produce incorrect information regarding allergens, ingredients, or macronutrient values. Always verify ingredients and nutritional content independently.

**No Liability:** The authors and contributors of this software are not liable for any adverse effects, health complications, or damages resulting from the use of the application or reliance on the information provided.

**Emergency:** If you think you may have a medical emergency, call your doctor or emergency services immediately.

By using this software, you acknowledge that you have read this disclaimer and agree to use the application at your own risk.