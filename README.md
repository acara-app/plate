# Plate - Personalized Nutrition & Meal Planning Platform

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Tests](https://github.com/acara-app/plate/actions/workflows/tests.yml/badge.svg)](https://github.com/acara-app/plate/actions/workflows/tests.yml)

Plate is an AI-powered personalized nutrition and meal planning platform that creates customized meal plans based on individual user data such as age, weight, height, dietary preferences, and health goals. The platform simplifies meal planning by providing users with tailored recipes, nutritional information, and glucose tracking capabilities that align with their unique needs and lifestyle.

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Code of Conduct

Please review our [Code of Conduct](CODE_OF_CONDUCT.md) before participating.

## About

Plate leverages advanced AI (Google Gemini) through PrismPHP to generate comprehensive 7-day meal plans that consider:

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

## Quick Start

### Prerequisites
- **PHP 8.4+** with Laravel 12.x
- **SQLite/PostgreSQL 17+** with pgvector extension
- **Node.js 20+** - frontend development
- **React 19** - Modern UI library
- **TypeScript** - Type-safe JavaScript
- **Inertia.js v2** - Modern monolith SPA framework
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **OpenAI (or Gemini/Anthropic) API Key** - embedding generation and AI analysis
- **PrismPHP** - LLM integration

## Setup & Installation

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd plate
```

2. **Install dependencies and setup**
```bash
composer setup
```
This will:
- Install Composer dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run migrations
- Install npm dependencies
- Build frontend assets

3. **Configure environment variables**
```bash
# Copy .env.example to .env (done by composer setup)
# Configure your database, mail, and API keys

# Required API Keys
GOOGLE_GEMINI_API_KEY=your_gemini_key # Or

STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key

# Optional: Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

4. **Seed the database** 

```bash
# Optional
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

## Testing

### Run All Tests
```bash
composer test
```

This runs:
- Type coverage check (must be 100%)
- Unit and feature tests with coverage (must be exactly 100%)
- Code style checks (Pint & Rector)
- Static analysis (PHPStan & TypeScript)

### Individual Test Commands

```bash
# Pest tests with coverage
composer test:unit

# Type coverage
composer test:type-coverage

# Code style
composer test:lint

# Static analysis
composer test:types

# Run specific test file
php artisan test tests/Feature/Actions/AiAgents/GenerateMealPlanTest.php

# Run with filter
php artisan test --filter=GenerateMealPlan
```

## Code Quality

### Linting & Formatting

```bash
# Fix all code style issues
composer lint
```

This runs:
- Rector (automated refactoring)
- Laravel Pint (PHP code formatting)
- ESLint (JavaScript/TypeScript)
- Prettier (code formatting)


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