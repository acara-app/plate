<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Learn how to log glucose, insulin, carbs, and more via Telegram. Step-by-step guide to tracking your health data hands-free using AI-powered natural language.', 'metaKeywords' => 'log glucose telegram, telegram health tracker, telegram health bot, log insulin via telegram, health data messenger app, ai health tracker telegram'])]
#[Title('Quick Health Logging with Telegram | Free AI-Powered Health Tracking')]
class extends Component
{
    public array $loggingExamples = [
        [
            'type' => 'Glucose',
            'icon' => '🩸',
            'examples' => [
                'My glucose is 140',
                'Fasting glucose 95 mg/dL',
                'Post-meal glucose 180',
            ],
        ],
        [
            'type' => 'Food / Carbs',
            'icon' => '🍎',
            'examples' => [
                'Ate 45g carbs',
                'Had lunch with 30g carbs',
                'Dinner was 60g carbs',
            ],
        ],
        [
            'type' => 'Insulin',
            'icon' => '💉',
            'examples' => [
                'Took 5 units of insulin',
                'Bolus 3 units',
                'Basal 20 units',
            ],
        ],
        [
            'type' => 'Medication',
            'icon' => '💊',
            'examples' => [
                'Took metformin 500mg',
                'Had my morning medication',
                'Took 10mg glipizide',
            ],
        ],
        [
            'type' => 'Vitals',
            'icon' => '❤️',
            'examples' => [
                'Weight 180 lbs',
                'BP 120/80',
                'Blood pressure 130/85',
            ],
        ],
        [
            'type' => 'Exercise',
            'icon' => '🏃',
            'examples' => [
                'Walked 30 minutes',
                'Ran 20 min',
                'Did 45 min yoga',
            ],
        ],
    ];

    public array $commands = [
        ['command' => '/start', 'description' => 'Welcome message and setup instructions'],
        ['command' => '/help', 'description' => 'Show all available commands'],
        ['command' => '/log', 'description' => 'Start logging health data'],
        ['command' => '/me', 'description' => 'Show your profile information'],
        ['command' => '/link', 'description' => 'Link your Telegram to your account'],
        ['command' => '/yes', 'description' => 'Confirm pending health log'],
        ['command' => '/no', 'description' => 'Cancel pending health log'],
    ];
};
?>

<x-slot:jsonLd>
    <x-json-ld.telegram-health-logging />
</x-slot:jsonLd>

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-blue-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 dark:text-slate-50"
>
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-blue-300/20 blur-3xl dark:bg-blue-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-6 w-full max-w-md lg:mb-8">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Card --}}
    <div class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-blue-500/10 dark:bg-slate-800 dark:shadow-blue-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-2xl dark:bg-blue-900/50">💬</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quick Health Logging with Telegram</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Log your health data hands-free using AI-powered natural language</p>
        </div>

    <main class="relative z-10 w-full max-w-md">
        <div class="rounded-3xl bg-white shadow-xl dark:bg-slate-800">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">
                    How to Connect
                </h2>

                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">1</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Open Telegram</strong>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Search for <strong>{{ config('plate.telegram_bot_username') }}</strong> or
                                <a href="https://t.me/{{ config('plate.telegram_bot_username') }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">click here to open</a>
                            </p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">2</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Start the Bot</strong>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Tap "Start" or send <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-700">/start</code> to begin
                            </p>
                        </div>
                    </li>
                </ol>

                <figure class="mt-4">
                    <img 
                        src="{{ asset('screenshots/telegram-bot-welcome-screen.webp') }}" 
                        alt="Telegram bot welcome screen showing @AcaraPlate_bot with Start button"
                        class="rounded-xl border border-slate-200 shadow-sm dark:border-slate-700"
                        width="600"
                        height="auto"
                        loading="lazy"
                    >
                    <figcaption class="mt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                        Tap "Start" to begin using the bot
                    </figcaption>
                </figure>

                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">3</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Link Your Account</strong>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Go to <a href="{{ route('integrations.edit') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Settings → Integrations</a> and generate a linking token
                            </p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">4</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Start Chatting!</strong>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Send <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-700">/link YOUR_TOKEN</code> to connect
                            </p>
                        </div>
                    </li>
                </ol>

                @auth
                    <a
                        href="{{ route('integrations.edit') }}"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-white font-medium hover:bg-blue-700 transition-colors"
                    >
                        Generate Linking Token
                    </a>
                @else
                    <a
                        href="{{ route('register') }}"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-white font-medium hover:bg-blue-700 transition-colors"
                    >
                        Create Free Account
                    </a>
                @endauth
            </div>
        </div>

        <div class="mt-6 rounded-3xl bg-white shadow-xl dark:bg-slate-800">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">
                    What You Can Log
                </h2>
                <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
                    Just describe your health data in natural language. The AI understands:
                </p>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($this->loggingExamples as $example)
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="text-xl">{{ $example['icon'] }}</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $example['type'] }}</span>
                            </div>
                            <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-400">
                                @foreach ($example['examples'] as $ex)
                                    <li class="truncate">"{{ $ex }}"</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <figure class="mt-4">
                    <img 
                        src="{{ asset('screenshots/telegram-bot-logging-glucose.webp') }}" 
                        alt="Telegram conversation showing user typing 'My glucose is 140' and bot responding with parsed glucose data"
                        class="rounded-xl border border-slate-200 shadow-sm dark:border-slate-700"
                        width="600"
                        height="auto"
                        loading="lazy"
                    >
                    <figcaption class="mt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                        Just type naturally - the AI understands what you're logging
                    </figcaption>
                </figure>
            </div>
        </div>

        <div class="mt-6 rounded-3xl bg-white shadow-xl dark:bg-slate-800">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">
                    Available Commands
                </h2>

                <div class="grid gap-2 text-sm">
                    @foreach ($this->commands as $cmd)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-700/50">
                            <code class="font-mono text-blue-600 dark:text-blue-400">{{ $cmd['command'] }}</code>
                            <span class="text-slate-600 dark:text-slate-400">{{ $cmd['description'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
            <strong>Note:</strong> You can also just send health data directly without commands. The AI will detect and parse it automatically!
        </p>
    </div>

    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="how-it-works-heading">
        <h2 id="how-it-works-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            How It Works
        </h2>
        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>AI-Powered Parsing:</strong> Our AI understands natural language and automatically detects what health data you're logging—glucose, carbs, insulin, medication, vitals, or exercise.</p>
            </div>
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>Smart Confirmations:</strong> Before saving, the bot shows you exactly what will be logged. Simply reply /yes to confirm or /no to cancel.</p>
            </div>

            <figure class="mt-2">
                <img 
                    src="{{ asset('screenshots/telegram-bot-confirmation-prompt.webp') }}" 
                    alt="Telegram bot confirmation prompt showing 'Log: Glucose 140 mg/dL (random) - Reply /yes to confirm or /no to cancel'"
                    class="rounded-xl border border-slate-200 shadow-sm dark:border-slate-700"
                    width="600"
                    height="auto"
                    loading="lazy"
                >
                <figcaption class="mt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                    Bot confirms before saving your health data
                </figcaption>
            </figure>
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>Unit Conversions:</strong> The AI automatically handles conversions—say "weight 180 lbs" and it saves in kilograms. Say "glucose 6.5" and it understands mmol/L.</p>
            </div>
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>Multi-Language Support:</strong> Log data in multiple languages. Try "mi glucosa es 140" or "ma glycémie est 7.2"—it understands!</p>
            </div>
        </div>
    </section>

    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Frequently Asked Questions
        </h2>

        <div class="space-y-3" x-data="{ openFaq: null }">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 1 ? null : 1"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span class="speakable-intro">How do I log glucose on Telegram?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-intro">Simply send a message like "My glucose is 140" or "Fasting glucose 95". The AI automatically detects it's glucose data, shows you the reading, and asks for confirmation. Reply /yes to save it to your health log.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 2 ? null : 2"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span class="speakable-how-it-works">Can I log insulin via Telegram?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-how-it-works">Yes! Just say "Took 5 units of insulin" or "Bolus 3 units". You can specify insulin type (basal, bolus, or mixed) and the bot will log it with your health data.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 3 ? null : 3"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>What health data can I track on Telegram?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>You can log glucose (mg/dL or mmol/L), food and carbs (grams), insulin (units), medication (name and dosage), vitals (weight, blood pressure), and exercise (type and duration).</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 4 ? null : 4"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Is Telegram secure for health data?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Your Telegram account is securely linked to your Plate account using a unique token. Health data is stored in your private Plate account, not on Telegram's servers. Telegram's end-to-end encryption protects your conversations.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 5 ? null : 5"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Does the bot understand different units?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Yes! The AI automatically converts units. Say "glucose 6.5" (mmol/L) or "glucose 140" (mg/dL)—it knows the difference. Say "weight 180 lbs" and it saves in kilograms. No need to do the math yourself.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 6 ? null : 6"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Can I ask nutrition questions too?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 6 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 6" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Absolutely! Your AI nutritionist is available 24/7. Ask questions like "What should I eat for breakfast?" or "Will pizza spike my glucose?" or "I'm at Chipotle, what should I order?"</p>
                </div>
            </div>
        </div>
    </section>

    <section class="relative z-10 mt-12 mb-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Explore More Free Tools
        </h2>
        <a href="{{ route('tools.index') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
            <span class="mb-2 text-3xl">🛠️</span>
            <h3 class="font-bold text-slate-900 dark:text-white">View All Free Tools</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Discover free health trackers, calculators, and nutrition tools</p>
        </a>
    </section>

    <x-footer />
</div>
