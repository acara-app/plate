# Plate - Personalized Nutrition & Meal Planning Platform

Plate is an AI-powered personalized nutrition and meal planning platform that creates customized meal plans based on individual user data such as age, weight, height, dietary preferences, and health goals. The platform simplifies meal planning by providing users with tailored recipes, nutritional information, and ... that align with their unique needs and lifestyle.

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

## Tech Stack

### Backend
- **Laravel 12** - Modern PHP framework with streamlined file structure
- **PHP 8.4** - Latest PHP version with performance improvements
- **PrismPHP** - AI integration for structured data generation
- **Google Gemini 2.5 Flash** - AI model for meal plan generation
- **Laravel Cashier** - Stripe integration for subscription management
- **Laravel Fortify** - Authentication scaffolding
- **Laravel Socialite** - OAuth authentication (Google)
- **SQLite/PostgreSQL** - Database support

### Frontend
- **React 19** - Modern UI library
- **TypeScript** - Type-safe JavaScript
- **Inertia.js v2** - Modern monolith SPA framework
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **shadcn/ui** - Beautiful, customizable components
- **Lucide React** - Icon library

### Development Tools
- **Vite** - Fast build tool and dev server
- **Pest 4** - Modern PHP testing framework with browser testing
- **Laravel Pint** - Opinionated PHP code formatter
- **Rector** - PHP automated refactoring tool
- **Larastan (PHPStan)** - Static analysis for Laravel
- **ESLint & Prettier** - JavaScript/TypeScript linting and formatting

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

4. **Subscription Features**
   - Stripe integration via Laravel Cashier
   - Subscription-based access to meal plans
   - Multiple subscription tiers

### Technical Features
- **Queue-based Processing**: Meal plan generation runs asynchronously
- **Job Tracking**: Real-time progress monitoring for long-running tasks
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Dark Mode**: Full dark mode support
- **Type Safety**: End-to-end TypeScript integration
- **Testing**: Comprehensive test coverage with Pest (unit, feature, and browser tests)
- **Action Pattern**: Business logic organized in reusable Action classes
- **Form Requests**: Dedicated validation classes following Laravel best practices
- **Wayfinder**: Type-safe routing for Inertia.js

## Project Structure

```
app/
├── Actions/              # Business logic actions
│   ├── AiAgents/        # AI-related actions (meal plan generation)
│   ├── CreateUser.php
│   ├── StoreMealPlan.php
│   └── ...
├── DataObjects/         # DTOs for structured data
├── Enums/               # Enums (AiModel, MealType, Sex, etc.)
├── Http/
│   ├── Controllers/     # HTTP controllers
│   └── Requests/        # Form request validation classes
├── Jobs/                # Queue jobs (ProcessMealPlanJob)
├── Models/              # Eloquent models
├── Services/            # Service classes
└── Traits/              # Reusable traits

resources/
├── css/                 # Stylesheets
├── js/
│   ├── components/      # React components
│   │   ├── ui/         # Radix UI components
│   │   └── meal-plans/ # Meal plan specific components
│   ├── pages/          # Inertia.js pages
│   │   ├── onboarding/ # Onboarding flow
│   │   ├── meal-plans/ # Meal plan views
│   │   └── dashboard.tsx
│   └── types/          # TypeScript type definitions
└── views/              # Blade views (welcome, privacy, terms, AI prompts)

tests/
├── Feature/            # Feature tests
├── Unit/               # Unit tests
└── Browser/            # Browser tests (Pest v4)
```

## Setup & Installation

### Prerequisites
- PHP 8.4 or higher
- Node.js 18+ and npm
- Composer
- SQLite or PostgreSQL

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
GOOGLE_GEMINI_API_KEY=your_gemini_key
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key

# Optional: Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

4. **Seed the database**
```bash
php artisan db:seed --class=GoalSeeder
php artisan db:seed --class=LifestyleSeeder
php artisan db:seed --class=DietaryPreferenceSeeder
php artisan db:seed --class=HealthConditionSeeder
php artisan db:seed --class=SubscriptionProductSeeder
```

5. **Setup Stripe webhooks** (for subscription handling)
```bash
php artisan cashier:webhook
```

## Development

### Running the Development Server

```bash
composer run dev
```

This starts multiple processes concurrently:
- Laravel development server (port 8000)
- Queue worker
- Laravel Pail (log viewer)
- Vite dev server (HMR)

### Individual Commands

```bash
# Start Laravel server only
php artisan serve

# Run queue worker
php artisan queue:listen --tries=1

# Watch logs
php artisan pail --timeout=0

# Frontend dev server
npm run dev

# Build for production
npm run build
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

### Manual Checks

```bash
# Check PHP code style
vendor/bin/pint --test

# Fix PHP code style
vendor/bin/pint

# Run PHPStan
vendor/bin/phpstan analyze

# TypeScript type checking
npm run test:types
```

## Deployment

The application includes a deployment script for Ploi:

```bash
./ploi-deployment.sh
```

### Production Build

```bash
npm run build
```

### Cloudflare Integration

The application includes scripts for Cloudflare cache purging:
```bash
./scripts/purge-cloudflare-cache.sh
```

## Documentation

Additional documentation is available in the `/docs` directory:

- `cloudflare-cache-purging.md` - Cloudflare cache management
- `cors-issue-resolution.md` - CORS configuration and troubleshooting
- `google-oauth-setup.md` - Google OAuth setup guide
- `search-queries.md` - SEO search query performance
- `stripe-webhook-local-testing.md` - Testing Stripe webhooks locally
- `testing-stripe-checkout-flow.md` - Stripe checkout testing guide
- `wayfinder-routes.md` - Wayfinder routing documentation

## Environment Variables

Key environment variables:

```bash
# Application
APP_NAME=Plate
APP_ENV=production
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=sqlite  # or pgsql

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_FROM_ADDRESS=hello@your-domain.com

# AI
GOOGLE_GEMINI_API_KEY=your_api_key

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# OAuth (Optional)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
```

## Contributing

1. Follow Laravel and React best practices
2. Use the Action pattern for business logic
3. Write tests for all new features (aim for 100% coverage)
4. Run `composer lint` before committing
5. Ensure all tests pass with `composer test`
6. Follow existing code conventions and structure

## License

This project is open-sourced software licensed under the MIT license.

## Support

For support and questions, visit the support page or contact the development team.
