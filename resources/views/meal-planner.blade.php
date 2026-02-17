@section('title', 'AI Meal Planner for Diabetes | Personalized 7-Day Plans')
@section('meta_description', 'AI-generated meal plans tailored for Type 2 Diabetes and Pre-diabetes. Manage glucose, lose weight, and eat with confidence.')
@section('meta_keywords', 'diabetes meal planner, meal planning for diabetics, AI meal planner, diabetic meal plan generator, digital meal planner diabetes')

<x-default-layout>
    <header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                <span>Acara Plate</span>
            </a>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden items-center px-4 py-2 text-sm font-medium text-slate-600 transition-all duration-200 hover:text-slate-900 sm:inline-flex">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                        Start for Free
                    </a>
                @endauth
            </div>
        </div>
    </header>

@section('head')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "AI Meal Planner for Diabetes",
        "description": "Get AI-generated meal plans tailored to your diabetes type, diet preferences, and glucose goals.",
        "publisher": {
            "@@type": "Organization",
            "name": "Acara Plate"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            {
                "@@type": "Question",
                "name": "How does the AI meal planner work for diabetes?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "The AI analyzes your diabetes type, medications, glucose goals, and dietary preferences to create meal plans that help stabilize blood sugar. Each meal includes macro breakdowns and glycemic impact predictions."
                }
            },
            {
                "@@type": "Question",
                "name": "Can I customize the meal plans?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Yes! You can specify food allergies, dietary restrictions, and preferences. You can also regenerate specific meals or entire days if you don't like the suggestions."
                }
            },
            {
                "@@type": "Question",
                "name": "How does this help with A1C and weight loss?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "By focusing on glucose stability and nutrient density, our plans help reduce insulin resistance and support healthy weight loss, key factors in managing Type 2 diabetes."
                }
            },
            {
                "@@type": "Question",
                "name": "What diet types are supported?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "The platform supports 8 diet types: Mediterranean, Low Carb, Keto, DASH, Paleo, Vegetarian, Vegan, and Balanced. Each is tailored for glucose management."
                }
            },
            {
                "@@type": "Question",
                "name": "How do I track my meals?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "You can log meals via the built-in AI chat or through the Telegram bot. Just send a message like 'Ate chicken salad for lunch' and the AI will parse and log it."
                }
            }
        ]
    }
    </script>
@endsection

<div class="mx-auto my-8 max-w-6xl px-4 sm:px-6 lg:px-8">
    {{-- Hero Section --}}
    <header class="text-center mb-12">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800 mb-6">
            <span class="flex h-2 w-2 rounded-full bg-emerald-600"></span>
            Designed for Type 2 Diabetes & Pre-diabetes
        </div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
            I Tried Every Meal Planning App. They All Wanted Me to Count Calories.
        </h1>
        <p class="mt-4 text-lg text-slate-600 max-w-2xl mx-auto">
            That's not sustainable. That's not enjoyable. And that's not how real people eat. What if meal planning actually fit your life instead of the other way around?
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-slate-600">
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                8 Diet Types
            </span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Glucose-Friendly
            </span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                AI-Generated
            </span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                AI Chat Support
            </span>
        </div>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-8 py-4 text-base font-semibold text-white shadow-lg transition-all duration-300 hover:bg-slate-800 hover:shadow-xl">
                Create Your Free Meal Plan
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
        <p class="mt-3 text-sm text-slate-500">Takes 2 minutes.</p>
    </header>

    {{-- How It Works --}}
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center text-slate-900 mb-8">How It Actually Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">1</div>
                <h3 class="mt-2 text-lg font-semibold text-slate-900">Tell It About Yourself</h3>
                <p class="mt-2 text-sm text-slate-600">Diabetes type, diet preferences (Mediterranean, Keto, Low Carb, etc.), and your glucose goals. Just describe what you're working with.</p>
            </div>
            <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">2</div>
                <h3 class="mt-2 text-lg font-semibold text-slate-900">AI Builds Your Plan</h3>
                <p class="mt-2 text-sm text-slate-600">A complete 7-day meal plan appears—breakfast, lunch, dinner, snacks—all balanced for your diet type and glucose goals. No math required.</p>
            </div>
            <div class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="absolute -top-3 left-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">3</div>
                <h3 class="mt-2 text-lg font-semibold text-slate-900">Track & Tweak</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Log meals through easy chat (Web or <a href="{{ route('telegram-health-logging') }}" class="text-emerald-600 hover:underline">Telegram</a>). See <a href="{{ route('spike-calculator') }}" class="text-emerald-600 hover:underline">glucose predictions</a> and adjust as you go.
                </p>
            </div>
        </div>
    </section>

    {{-- Diet Types Showcase --}}
    <section class="mb-16">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Plans for Every Eating Style</h2>
            <p class="mt-2 text-slate-600">Whether you're managing Type 2 diabetes, pre-diabetes, or just want better glucose control, there's a plan that fits. Here's what each one looks like.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                <h3 class="text-lg font-semibold text-slate-900">🫒 Mediterranean</h3>
                <p class="mt-2 text-sm text-slate-600">Gold standard for diabetes. High healthy fats, lean proteins, whole grains.</p>
                <span class="mt-3 inline-block text-xs font-medium text-emerald-700">45% Carbs • 18% Protein • 37% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">🥑 Low Carb</h3>
                <p class="mt-2 text-sm text-slate-600">Reduced carbohydrates for better glucose control. Diabetic-friendly.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">20% Carbs • 35% Protein • 45% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">🔥 Keto <span class="text-xs font-normal text-slate-500">*</span></h3>
                <p class="mt-2 text-sm text-slate-600">Very low carb for metabolic management. Strict but effective. <span class="text-xs text-slate-400 block mt-1">* Check medications with your doctor.</span></p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">5% Carbs • 20% Protein • 75% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">❤️ DASH</h3>
                <p class="mt-2 text-sm text-slate-600">Heart-healthy eating with low sodium. Great for blood pressure + glucose.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">52% Carbs • 18% Protein • 30% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">🥩 Paleo</h3>
                <p class="mt-2 text-sm text-slate-600">Ancestral eating. No grains or dairy, focus on whole foods.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">30% Carbs • 35% Protein • 35% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">🌱 Vegetarian</h3>
                <p class="mt-2 text-sm text-slate-600">Plant-based with eggs/dairy. Focus on high-fiber complex carbs.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">55% Carbs • 15% Protein • 30% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">🌿 Vegan</h3>
                <p class="mt-2 text-sm text-slate-600">Strictly plant-based. Emphasizing low-glycemic whole foods.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">60% Carbs • 14% Protein • 26% Fat</span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-lg font-semibold text-slate-900">⚖️ Balanced</h3>
                <p class="mt-2 text-sm text-slate-600">Standard USDA guidelines. Moderate mix of all macros.</p>
                <span class="mt-3 inline-block text-xs font-medium text-slate-600">50% Carbs • 20% Protein • 30% Fat</span>
            </div>
        </div>
        <p class="mt-4 text-center text-sm text-slate-500">
            <a href="{{ route('food.index') }}" class="text-emerald-600 hover:underline">Learn about glycemic index</a> and how different foods affect your glucose levels.
        </p>
    </section>

    {{-- Sample 3-Day Preview --}}
    <section class="mb-16" x-data="{ activeTab: 'mediterranean' }">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-900">See What You're Actually Getting</h2>
            <p class="mt-2 text-slate-600">Here's what real meal plans look like for different diet types.</p>
            
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <button 
                    @click="activeTab = 'mediterranean'"
                    :class="activeTab === 'mediterranean' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition-all">
                    Mediterranean
                </button>
                <button 
                    @click="activeTab = 'low_carb'"
                    :class="activeTab === 'low_carb' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition-all">
                    Low Carb
                </button>
                <button 
                    @click="activeTab = 'vegetarian'"
                    :class="activeTab === 'vegetarian' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition-all">
                    Vegetarian
                </button>
            </div>
        </div>

        {{-- Mediterranean Grid --}}
        <div x-show="activeTab === 'mediterranean'" x-transition.opacity class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 1</h3>
                <div class="space-y-4">
                    <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Greek Yogurt with Berries & Walnuts</p>
                        <span class="text-xs text-slate-500">320 cal • 12g protein • 28g carbs • <strong>6g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Grilled Chicken Salad with Olive Oil</p>
                        <span class="text-xs text-slate-500">450 cal • 35g protein • 15g carbs • <strong>7g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Apple slices with Almond Butter</p>
                        <span class="text-xs text-slate-500">200 cal • 5g protein • 18g carbs • <strong>4g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Baked Salmon with Roasted Veggies</p>
                        <span class="text-xs text-slate-500">580 cal • 38g protein • 22g carbs • <strong>8g fiber</strong></span>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 2</h3>
                <div class="space-y-4">
                    <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Avocado Toast with Poached Eggs</p>
                        <span class="text-xs text-slate-500">380 cal • 18g protein • 22g carbs • <strong>9g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Mediterranean Quinoa Bowl</p>
                        <span class="text-xs text-slate-500">420 cal • 15g protein • 45g carbs • <strong>10g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Carrot Sticks with Hummus</p>
                        <span class="text-xs text-slate-500">150 cal • 4g protein • 12g carbs • <strong>4g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Turkey Meatballs with Zucchini Noodles</p>
                        <span class="text-xs text-slate-500">520 cal • 32g protein • 18g carbs • <strong>5g fiber</strong></span>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 3</h3>
                <div class="space-y-4">
                    <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Steel-Cut Oats with Blueberries</p>
                        <span class="text-xs text-slate-500">340 cal • 10g protein • 48g carbs • <strong>8g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Tuna Salad Stuffed Tomatoes</p>
                        <span class="text-xs text-slate-500">380 cal • 28g protein • 12g carbs • <strong>5g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Greek Yogurt with Chia Seeds</p>
                        <span class="text-xs text-slate-500">180 cal • 12g protein • 8g carbs • <strong>3g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Herb-Roasted Chicken with Asparagus</p>
                        <span class="text-xs text-slate-500">550 cal • 42g protein • 14g carbs • <strong>6g fiber</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Carb Grid --}}
        <div x-show="activeTab === 'low_carb'" style="display: none;" x-transition.opacity class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 1</h3>
                <div class="space-y-4">
                    <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Spinach & Feta Omelet</p>
                        <span class="text-xs text-slate-500">340 cal • 24g protein • 5g carbs • <strong>2g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Cobb Salad (No Croutons)</p>
                        <span class="text-xs text-slate-500">480 cal • 35g protein • 10g carbs • <strong>5g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">String Cheese & Almonds</p>
                        <span class="text-xs text-slate-500">160 cal • 9g protein • 3g carbs • <strong>2g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Grilled Salmon with Asparagus</p>
                        <span class="text-xs text-slate-500">560 cal • 42g protein • 8g carbs • <strong>4g fiber</strong></span>
                    </div>
                </div>
            </div>
             <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 2</h3>
                <div class="space-y-4">
                     <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Chia Seed Pudding with Almond Milk</p>
                        <span class="text-xs text-slate-500">280 cal • 10g protein • 12g carbs • <strong>10g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Turkey Lettuce Wraps</p>
                        <span class="text-xs text-slate-500">350 cal • 30g protein • 8g carbs • <strong>3g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Hard Boiled Egg</p>
                        <span class="text-xs text-slate-500">70 cal • 6g protein • 1g carbs • <strong>0g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Chicken Thighs with Cauliflower Mash</p>
                        <span class="text-xs text-slate-500">550 cal • 35g protein • 10g carbs • <strong>5g fiber</strong></span>
                    </div>
                </div>
            </div>
             <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 3</h3>
                <div class="space-y-4">
                     <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Sausage & Mushroom Frittata</p>
                        <span class="text-xs text-slate-500">380 cal • 25g protein • 6g carbs • <strong>2g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Sardine Salad with Avocado</p>
                        <span class="text-xs text-slate-500">420 cal • 30g protein • 5g carbs • <strong>6g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Macadamia Nuts</p>
                        <span class="text-xs text-slate-500">200 cal • 2g protein • 4g carbs • <strong>3g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Beef Stir-Fry (No Rice)</p>
                        <span class="text-xs text-slate-500">500 cal • 40g protein • 12g carbs • <strong>4g fiber</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Vegetarian Grid --}}
        <div x-show="activeTab === 'vegetarian'" style="display: none;" x-transition.opacity class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 1</h3>
                 <div class="space-y-4">
                    <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Tofu Scramble with Spinach</p>
                        <span class="text-xs text-slate-500">280 cal • 18g protein • 8g carbs • <strong>4g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Lentil & Vegetable Soup</p>
                        <span class="text-xs text-slate-500">350 cal • 18g protein • 40g carbs • <strong>12g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Pear & Pistachios</p>
                        <span class="text-xs text-slate-500">200 cal • 6g protein • 25g carbs • <strong>6g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Chickpea Curry with Brown Rice</p>
                        <span class="text-xs text-slate-500">450 cal • 16g protein • 55g carbs • <strong>10g fiber</strong></span>
                    </div>
                </div>
            </div>
             <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 2</h3>
                 <div class="space-y-4">
                     <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Oatmeal with Peanut Butter</p>
                        <span class="text-xs text-slate-500">350 cal • 10g protein • 42g carbs • <strong>8g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Caprese Salad with Chickpeas</p>
                        <span class="text-xs text-slate-500">380 cal • 15g protein • 18g carbs • <strong>6g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Edamame</p>
                        <span class="text-xs text-slate-500">120 cal • 11g protein • 10g carbs • <strong>5g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Eggplant Parmesan (Light)</p>
                        <span class="text-xs text-slate-500">420 cal • 20g protein • 35g carbs • <strong>9g fiber</strong></span>
                    </div>
                </div>
            </div>
             <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Day 3</h3>
                 <div class="space-y-4">
                     <div class="border-l-4 border-emerald-500 pl-3">
                        <span class="text-xs font-medium text-emerald-700">🌅 Breakfast</span>
                        <p class="text-sm font-medium text-slate-900">Smoothie Bowl (Spinach, Banana, Protein)</p>
                        <span class="text-xs text-slate-500">300 cal • 20g protein • 38g carbs • <strong>6g fiber</strong></span>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-3">
                        <span class="text-xs font-medium text-amber-700">☀️ Lunch</span>
                        <p class="text-sm font-medium text-slate-900">Black Bean Quesadilla on Low-Carb Tortilla</p>
                        <span class="text-xs text-slate-500">400 cal • 18g protein • 35g carbs • <strong>12g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-blue-500 pl-3">
                        <span class="text-xs font-medium text-blue-700">🫐 Snack</span>
                        <p class="text-sm font-medium text-slate-900">Apple & Walnut Slices</p>
                        <span class="text-xs text-slate-500">180 cal • 3g protein • 15g carbs • <strong>3g fiber</strong></span>
                    </div>
                     <div class="border-l-4 border-purple-500 pl-3">
                        <span class="text-xs font-medium text-purple-700">🌙 Dinner</span>
                        <p class="text-sm font-medium text-slate-900">Vegetable Stir-Fry with Tempeh</p>
                        <span class="text-xs text-slate-500">450 cal • 25g protein • 25g carbs • <strong>8g fiber</strong></span>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="mt-8 text-center text-sm text-slate-500">
            <strong>Want a static PDF-style plan?</strong> <a href="{{ route('10-day-meal-plan') }}" class="text-emerald-600 font-medium hover:underline">View our full 10-Day Meal Plan</a> or generate your own custom plan below.
        </p>

        <p class="mt-4 text-center text-sm text-slate-500">
            <strong>AI-Powered:</strong> Your actual plan is personalized based on your profile, preferences, and glucose goals.
        </p>
        <div class="mt-6 text-center">
            <a href="{{ route('register') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50">
                Get Your Personalized Plan
            </a>
        </div>
    </section>

    {{-- Unique Features --}}
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center text-slate-900 mb-8">What Makes This Different</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 text-orange-600 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">
                    <a href="{{ route('spike-calculator') }}" class="hover:text-emerald-600">Glucose Prediction</a>
                </h3>
                <p class="mt-2 text-sm text-slate-600">Every meal shows predicted glucose impact. Know your spike before you eat.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">
                    <a href="{{ route('telegram-health-logging') }}" class="hover:text-emerald-600">AI Chat Support</a>
                </h3>
                <p class="mt-2 text-sm text-slate-600">Chat with your AI nutritionist directly on the web or via Telegram. Get meal recommendations, answer questions, and track your progress.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">
                    <a href="{{ route('tools.index') }}" class="hover:text-emerald-600">Adaptive Plans</a>
                </h3>
                <p class="mt-2 text-sm text-slate-600">Your plan evolves based on your glucose data and feedback.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">Grocery Lists</h3>
                <p class="mt-2 text-sm text-slate-600">Auto-generated shopping lists organized by grocery store aisle.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-teal-600 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">Progress Tracking</h3>
                <p class="mt-2 text-sm text-slate-600">See trends in your glucose, weight, and adherence over time.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 mb-3">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">Open Source</h3>
                <p class="mt-2 text-sm text-slate-600">Transparent, community-driven, and privacy-focused.</p>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center text-slate-900 mb-8">Questions People Actually Ask</h2>
        <div class="space-y-4 max-w-3xl mx-auto">
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>How does the AI meal planner work for diabetes?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">The AI analyzes your diabetes type, medications, glucose goals, and dietary preferences to create meal plans that help stabilize blood sugar. Each meal includes macro breakdowns and glycemic impact predictions.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>Can I customize the meal plans?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">Yes! You can specify food allergies, dietary restrictions, and preferences. You can also regenerate specific meals or entire days if you don't like the suggestions.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>How does this help with A1C and weight loss?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">By focusing on glucose stability and nutrient density, our plans help reduce insulin resistance and support healthy weight loss, key factors in managing Type 2 diabetes.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>What diet types are supported?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">The platform supports 8 diet types: Mediterranean, Low Carb, Keto, DASH, Paleo, Vegetarian, Vegan, and Balanced. Each is tailored for glucose management.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>How do I track my meals?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">
                    You can log meals via the built-in AI chat or through the <a href="{{ route('telegram-health-logging') }}" class="text-emerald-600 hover:underline">Telegram bot</a>. Just send a message like "Ate chicken salad for lunch" and the AI will parse and log it.
                </p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <summary class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900">
                    <h3>Is Acara Plate free to use?</h3>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm text-slate-600">Yes! The core features are free and always will be — including AI-generated meal plans, glucose tracking, and the AI nutritionist chat. We're exploring optional premium features like advanced analytics for power users in the future.</p>
            </details>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="mb-12 text-center">
        <div class="rounded-2xl bg-linear-to-br from-emerald-50 to-teal-50 border border-emerald-200 p-8">
            <h2 class="text-2xl font-bold text-slate-900">Give It a Try</h2>
            <p class="mt-2 text-slate-600 max-w-xl mx-auto">Join thousands of people managing their diabetes with meal plans that actually fit their life.</p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-8 py-4 text-base font-bold text-white shadow-lg transition-all hover:bg-emerald-500 hover:shadow-xl">
                    Create Free Account
                </a>
            </div>
            <p class="mt-4 text-sm text-slate-500">
                <a href="{{ route('about') }}" class="text-emerald-600 hover:underline">About the approach</a>
            </p>
        </div>
    </section>

    {{-- See All Tools --}}
    <section class="border-t border-slate-200 pt-8">
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide mb-4">Free Diabetes Tools</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('spike-calculator') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 p-4 transition-all hover:border-orange-300 hover:bg-orange-50">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                <div>
                    <span class="font-medium text-slate-900 group-hover:text-orange-700">Spike Calculator</span>
                    <p class="text-xs text-slate-500">Check glucose impact</p>
                </div>
            </a>
            <a href="{{ route('snap-to-track') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 p-4 transition-all hover:border-blue-300 hover:bg-blue-50">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <div>
                    <span class="font-medium text-slate-900 group-hover:text-blue-700">Snap to Track</span>
                    <p class="text-xs text-slate-500">Photo food logging</p>
                </div>
            </a>
            <a href="{{ route('tools.index') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 p-4 transition-all hover:border-emerald-300 hover:bg-emerald-50">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </span>
                <div>
                    <span class="font-medium text-slate-900 group-hover:text-emerald-700">See All Tools</span>
                    <p class="text-xs text-slate-500">Full tool directory</p>
                </div>
            </a>
        </div>
    </section>
</div>

<x-footer />

</x-default-layout>
