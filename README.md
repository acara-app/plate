![Acara Plate - Personalized Nutrition AI](public/banner-acara-plate.webp)

# Acara Plate - Personalized Nutrition AI Agent

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

### OpenFoodFacts Integration

To address the challenge of accurate nutritional data, Acara Plate integrates with [OpenFoodFacts](https://world.openfoodfacts.org/), the world's largest open food database with over 3 million products contributed by users worldwide.

**Why OpenFoodFacts?**
- **Verified Data**: Access to real product nutritional information from food labels
- **Crowdsourced Accuracy**: Community-driven database continuously updated and verified
- **No Cost**: Free, open-source alternative to building and maintaining a proprietary food database
- **Global Coverage**: Supports products from multiple countries and regions
- **Transparency**: All data is open and auditable

**How It Works:**
1. **AI Generation**: Our AI creates personalized meal plans with ingredient lists
2. **Automatic Verification**: Each ingredient is cross-referenced against OpenFoodFacts database
3. **Nutrition Correction**: AI estimates are validated and corrected using verified data from real products
4. **Quality Scoring**: Each meal receives a confidence score based on verification rate
5. **Transparency**: Verification metadata shows which ingredients were matched and data sources used

This hybrid approach combines the personalization power of AI with the accuracy of real-world product data, reducing nutritional estimation errors and hallucinations while maintaining meal plan customization.

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

After setup, you may need to configure AI provider keys (set only those you will use) and optional OAuth variables in your `.env` file. Prism supports multiple LLM providers seamlessly:

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

# Optional: Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

Prism will use the provider(s) you reference in code; unused keys can be omitted.

### Database Seeding

Optionally, you can seed the database with initial data:

```bash
php artisan db:seed --class=GoalSeeder
php artisan db:seed --class=LifestyleSeeder
php artisan db:seed --class=DietaryPreferenceSeeder
php artisan db:seed --class=HealthConditionSeeder
```

### USDA Food Database Import

Acara Plate uses the USDA FoodData Central database for accurate nutritional information. To import the food data:

1. **Download USDA Food Data** from [FoodData Central](https://fdc.nal.usda.gov/download-datasets):
   - Foundation Foods (JSON format)
   - SR Legacy Foods (JSON format)

2. **Place the downloaded files** in `storage/sources/` directory

3. **Import the data** using the provided Artisan commands:

```bash
# Import Foundation Foods (default path)
php artisan import:usda-food-foundation-data

# Import SR Legacy Foods (default path)
php artisan import:usda-sr-legacy-food-data

# Or specify custom file paths
php artisan import:usda-food-foundation-data --path=/path/to/foundation.json
php artisan import:usda-sr-legacy-food-data --path=/path/to/legacy.json
```

**Performance Notes:**
- The import uses streaming to handle large JSON files efficiently
- Foundation Foods: ~1,200 entries (~2-5 seconds)
- SR Legacy Foods: ~8,000+ entries (~10-30 seconds)
- Imports use database transactions for data integrity
- Progress is displayed in real-time during import

**Database Optimization:**
- Fulltext indexes are automatically created on the `description` column for fast searches
- Indexes are only created on MySQL/PostgreSQL (skipped on SQLite for testing)
- The service uses case-insensitive LIKE queries with proper parameter binding

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

**AI Limitations:** The meal plans and nutritional data are generated by Artificial Intelligence via large language models (e.g., OpenAI, Anthropic Claude, Google Gemini, DeepSeek, Groq, Mistral) integrated through PrismPHP. While we strive for accuracy, LLMs can occasionally hallucinate or produce incorrect information regarding allergens, ingredients, or macronutrient values. Always verify ingredients and nutritional content independently.

**No Liability:** The authors and contributors of this software are not liable for any adverse effects, health complications, or damages resulting from the use of the application or reliance on the information provided.

**Emergency:** If you think you may have a medical emergency, call your doctor or emergency services immediately.

By using this software, you acknowledge that you have read this disclaimer and agree to use the application at your own risk.