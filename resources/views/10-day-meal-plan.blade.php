@section('title', '10-Day Diabetes-Friendly Meal Plan | Free Download | Acara Plate')
@section('meta_description', 'Get a complete 10-day meal plan with breakfast, lunch, dinner, and snacks. Designed for Type 2 diabetes and prediabetes management. Start eating better today!')

<x-default-layout>
    @section('head')
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Article",
            "headline": "10-Day Diabetes-Friendly Meal Plan",
            "description": "A complete 10-day meal plan designed for managing Type 2 diabetes and prediabetes with breakfast, lunch, dinner, and snacks.",
            "author": {
                "@@type": "Organization",
                "name": "Acara Plate"
            }
        }
        </script>
    @endsection

    <div class="mx-auto my-8 max-w-4xl px-4 sm:px-6 lg:px-8">
        {{-- Hero Section --}}
        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                10-Day Meal Plan
            </h1>
            <p class="mt-3 text-lg text-slate-600 max-w-2xl mx-auto">
                A complete meal plan with breakfast, lunch, dinner, and snacks—designed to help you manage blood sugar levels naturally.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-lg transition-all duration-300 hover:bg-slate-800 hover:shadow-xl">
                    Get Personalized Meal Plans
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('spike-calculator') }}"
                    class="inline-flex items-center gap-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Try Spike Calculator — Free
                </a>
            </div>
        </header>

        {{-- Day Navigation Tabs --}}
        <nav class="mb-6" aria-label="Day navigation">
            <div class="flex overflow-x-auto gap-2 pb-2 scrollbar-thin" id="day-tabs">
                @for ($day = 1; $day <= 10; $day++)
                    <button
                        onclick="showDay({{ $day }})"
                        id="tab-{{ $day }}"
                        class="day-tab shrink-0 px-4 py-2.5 rounded-full text-sm font-medium transition-all duration-200 {{ $day === 1 ? 'bg-slate-900 text-white shadow-md hover:bg-slate-800' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        aria-selected="{{ $day === 1 ? 'true' : 'false' }}"
                        role="tab">
                        Day {{ $day }}
                    </button>
                @endfor
            </div>
        </nav>

        {{-- Meal Plan Content --}}
        <div class="space-y-4" id="meal-plan-content">
            @php
                $mealPlanData = [
                    1 => [
                        'breakfast' => ['Cold cereal with milk, topped with nuts'],
                        'morning_snack' => ['Fruit'],
                        'lunch' => ['Fish, meat or cheese sandwich (whole-grain bread is a good choice)', 'Garden Tomato Salad'],
                        'afternoon_snack' => ['Low-Calorie Vegetable Soup'],
                        'dinner' => ['Shake-and-Bake Chicken', 'Low-Carb "Potato" Salad (Cauliflower)', 'Mixed vegetables', 'Fruit'],
                        'evening_snack' => ['Blueberry Oatmeal Muffin'],
                    ],
                    2 => [
                        'breakfast' => ['English muffin with cheese'],
                        'morning_snack' => ['Chai tea or chilled coffee made with milk'],
                        'lunch' => ['Quinoa Salad or Vermicelli Salad', 'Hard-boiled egg'],
                        'afternoon_snack' => ['Sugar-free gelatin'],
                        'dinner' => ['Fish Tacos', 'Poppy Seed Spinach Salad', 'Brownie'],
                        'evening_snack' => ['Fruit'],
                    ],
                    3 => [
                        'breakfast' => ['Scrambled egg made with added vegetables', 'Toast'],
                        'morning_snack' => ['Peanut Butter Oatmeal Cookie'],
                        'lunch' => ['Turkey Noodle Soup', 'Open-faced toasted tomato and cheese sandwich'],
                        'afternoon_snack' => ['Pineapple Coleslaw'],
                        'dinner' => ['Slow Cooker Pot Roast', 'Glass of milk', 'Apple Crumble'],
                        'evening_snack' => ['Popcorn'],
                    ],
                    4 => [
                        'breakfast' => ['Hot cereal with milk, topped with nuts'],
                        'morning_snack' => ['Fruit'],
                        'lunch' => ['Broccoli Cheese Soup or Rutabaga Leek Soup', 'Crackers with hummus', 'Celery sticks'],
                        'afternoon_snack' => ['2 Slim Bits or a protein bar'],
                        'dinner' => ['Vegetarian Chili', 'Sweet Potato Fries', 'Avocado Chocolate Pudding'],
                        'evening_snack' => ['Toasted waffle with reduced-sugar pancake syrup', 'Sugar-free iced tea'],
                    ],
                    5 => [
                        'breakfast' => ['Toast with peanut butter', 'Glass of milk'],
                        'morning_snack' => ['Greek-style yogurt'],
                        'lunch' => ['Beef Barley Soup or Three Sisters Hamburger Soup', 'Crackers and cheese', 'Fruit'],
                        'afternoon_snack' => ['Carrot sticks'],
                        'dinner' => ['Chicken Cobb Salad', 'Small bun with butter', 'Slice of Crustless Lemon Meringue Pie'],
                        'evening_snack' => ['Sparkling cranberry water'],
                    ],
                    6 => [
                        'breakfast' => ['Oatmeal muesli with chia seeds and Greek yogurt'],
                        'morning_snack' => ['Crackers with hummus'],
                        'lunch' => ['Bacon and egg', 'Toast', 'Tomatoes', 'Fruit'],
                        'afternoon_snack' => ['Small yogurt'],
                        'dinner' => ['Pizza', 'Caesar Salad', 'Glass of wine or spritzer', 'Raspberry Cream'],
                        'evening_snack' => ['Herbal tea'],
                    ],
                    7 => [
                        'breakfast' => ['Tortilla filled with scrambled egg and veggies', 'Fruit'],
                        'morning_snack' => ['Low-Calorie Vegetable Soup'],
                        'lunch' => ['Sushi rolls with sliced avocado and sliced sweet peppers'],
                        'afternoon_snack' => ['Slice of Grandma\'s Zucchini Loaf'],
                        'dinner' => ['Barbecue Pork Chops with Grilled Vegetables', 'Ice Cream Sundae'],
                        'evening_snack' => ['Diet drink'],
                    ],
                    8 => [
                        'breakfast' => ['Piece of cold pizza', 'Sliced fruit'],
                        'morning_snack' => ['Piece of cheese'],
                        'lunch' => ['Tuna or salmon sandwich', 'Carl\'s Red Cabbage Slaw'],
                        'afternoon_snack' => ['Sugar-free lemonade'],
                        'dinner' => ['Taco Bean Salad', 'Glass of milk', 'Fruit with Lime Topping'],
                        'evening_snack' => ['Small bowl of cold cereal with milk'],
                    ],
                    9 => [
                        'breakfast' => ['Banana Oatmeal Pancake with peanut butter'],
                        'morning_snack' => ['10 pecans or 20 pistachios'],
                        'lunch' => ['Split Pea Soup or Lentil Spinach Soup', 'Crackers', 'Raw veggies'],
                        'afternoon_snack' => ['Fruit'],
                        'dinner' => ['Spaghetti Squash Casserole', 'Everyday Salad', 'Slice of Ginger Pear Cake'],
                        'evening_snack' => ['Cup of light hot chocolate'],
                    ],
                    10 => [
                        'breakfast' => ['Avocado Spinach Smoothie'],
                        'morning_snack' => ['Fruit'],
                        'lunch' => ['Peanut butter and banana sandwich', 'Pumpkin Soup or Fall Tomato Cucumber Soup'],
                        'afternoon_snack' => ['Diet soft drink'],
                        'dinner' => ['Chicken Souvlaki with Tzatziki', 'Rice or pita', 'Greek Salad', 'Fresh fruit sprinkled with cinnamon'],
                        'evening_snack' => ['Glass of tomato juice or tomato-vegetable cocktail juice'],
                    ],
                ];

                $mealTypes = [
                    'breakfast' => ['label' => 'Breakfast', 'icon' => '🌅', 'color' => 'emerald'],
                    'morning_snack' => ['label' => 'Morning Snack', 'icon' => '☀️', 'color' => 'amber'],
                    'lunch' => ['label' => 'Lunch', 'icon' => '🍽️', 'color' => 'teal'],
                    'afternoon_snack' => ['label' => 'Afternoon Snack', 'icon' => '🍎', 'color' => 'orange'],
                    'dinner' => ['label' => 'Dinner', 'icon' => '🌙', 'color' => 'purple'],
                    'evening_snack' => ['label' => 'Evening Snack', 'icon' => '🌜', 'color' => 'slate'],
                ];
            @endphp

            @for ($day = 1; $day <= 10; $day++)
                <div id="day-{{ $day }}" class="day-content {{ $day !== 1 ? 'hidden' : '' }}">
                    <div class="space-y-3">
                        @foreach ($mealTypes as $mealKey => $mealInfo)
                            @php
                                $colorClasses = [
                                    'emerald' => 'border-l-emerald-500 bg-emerald-50/50',
                                    'amber' => 'border-l-amber-500 bg-amber-50/50',
                                    'teal' => 'border-l-teal-500 bg-teal-50/50',
                                    'orange' => 'border-l-orange-500 bg-orange-50/50',
                                    'purple' => 'border-l-purple-500 bg-purple-50/50',
                                    'slate' => 'border-l-slate-400 bg-slate-50/50',
                                ];
                                $textColors = [
                                    'emerald' => 'text-emerald-700',
                                    'amber' => 'text-amber-700',
                                    'teal' => 'text-teal-700',
                                    'orange' => 'text-orange-700',
                                    'purple' => 'text-purple-700',
                                    'slate' => 'text-slate-700',
                                ];
                            @endphp
                            <article class="rounded-xl border border-slate-200 border-l-4 {{ $colorClasses[$mealInfo['color']] }} p-4 shadow-sm transition-all duration-200 hover:shadow-md">
                                <header class="flex items-center gap-2 mb-2">
                                    <span class="text-lg" aria-hidden="true">{{ $mealInfo['icon'] }}</span>
                                    <h2 class="font-semibold {{ $textColors[$mealInfo['color']] }}">{{ $mealInfo['label'] }}</h2>
                                </header>
                                <ul class="space-y-1 pl-7 text-sm text-slate-700">
                                    @foreach ($mealPlanData[$day][$mealKey] as $item)
                                        <li class="flex items-start gap-2">
                                            <span class="text-slate-400 mt-1.5 h-1 w-1 rounded-full bg-slate-400 shrink-0"></span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>

                    {{-- Mid-page CTA (show after Day 5) --}}
                    @if ($day === 5)
                        <div class="mt-6 rounded-2xl bg-linear-to-r from-orange-500 to-amber-500 p-6 text-white shadow-lg">
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="text-lg font-bold">Wondering if a food will spike your blood sugar?</h3>
                                    <p class="mt-1 text-orange-100 text-sm">Check any food instantly with our free Spike Calculator.</p>
                                </div>
                                <a href="{{ route('spike-calculator') }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-orange-600 shadow-md transition-all hover:bg-orange-50">
                                    Try Spike Calculator
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Snap to Track Promo --}}
        <section class="mt-10 rounded-2xl bg-slate-900 p-6 text-white shadow-xl">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-blue-500/20">
                    <svg class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <h2 class="text-xl font-bold">Track Your Meals Instantly</h2>
                    <p class="mt-1 text-slate-400">Snap a photo of your food and get nutrition facts immediately—no typing required.</p>
                </div>
                <a href="{{ route('snap-to-track') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:bg-blue-500">
                    Try Snap to Track
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </section>

        {{-- Diabetes Log Book Link --}}
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">Need to track your glucose readings?</h3>
                    <p class="mt-1 text-sm text-slate-600">Download our free printable <a href="{{ route('diabetes-log-book') }}" class="text-emerald-600 font-medium hover:underline">Diabetes Log Book</a> to monitor your blood sugar levels daily.</p>
                </div>
            </div>
        </section>

        {{-- Bottom CTA --}}
        <section class="mt-10 text-center">
            <div class="rounded-2xl bg-linear-to-br from-emerald-50 to-teal-50 border border-emerald-200 p-8">
                <h2 class="text-2xl font-bold text-slate-900">Ready for Personalized Meal Plans?</h2>
                <p class="mt-2 text-slate-600 max-w-xl mx-auto">
                    Get AI-powered meal plans tailored to your glucose levels, dietary preferences, and health goals.
                </p>
                <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-8 py-4 text-base font-bold text-white shadow-lg transition-all hover:bg-emerald-500 hover:shadow-xl">
                        Start Your Free Plan
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
                <p class="mt-4 text-xs text-slate-500">Free to start • No credit card required • Open source</p>
            </div>
        </section>

        {{-- Related Links --}}
        <nav class="mt-10 border-t border-slate-200 pt-8" aria-label="Related tools">
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
                        </svg>
                    </span>
                    <div>
                        <span class="font-medium text-slate-900 group-hover:text-blue-700">Snap to Track</span>
                        <p class="text-xs text-slate-500">Photo food logging</p>
                    </div>
                </a>
                <a href="{{ route('diabetes-log-book') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 p-4 transition-all hover:border-emerald-300 hover:bg-emerald-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <div>
                        <span class="font-medium text-slate-900 group-hover:text-emerald-700">Diabetes Log Book</span>
                        <p class="text-xs text-slate-500">Track glucose levels</p>
                    </div>
                </a>
            </div>
        </nav>
    </div>

    <x-footer />

    <script>
        function showDay(day) {
            document.querySelectorAll('.day-content').forEach(el => {
                el.classList.add('hidden');
            });

            document.getElementById('day-' + day).classList.remove('hidden');

            document.querySelectorAll('.day-tab').forEach(tab => {
                tab.classList.remove('bg-slate-900', 'text-white', 'shadow-md', 'hover:bg-slate-800');
                tab.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
                tab.setAttribute('aria-selected', 'false');
            });

            const activeTab = document.getElementById('tab-' + day);
            activeTab.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
            activeTab.classList.add('bg-slate-900', 'text-white', 'shadow-md', 'hover:bg-slate-800');
            activeTab.setAttribute('aria-selected', 'true');

            activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    </script>

    <style>
        /* Custom scrollbar for day tabs */
        #day-tabs::-webkit-scrollbar {
            height: 0.25rem;
        }
        #day-tabs::-webkit-scrollbar-track {
            background: transparent;
        }
        #day-tabs::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 0.25rem;
        }
        #day-tabs {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
    </style>
</x-default-layout>
