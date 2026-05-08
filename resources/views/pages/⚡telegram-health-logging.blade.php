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

@php
    $botUsername = config('messaging.platforms.telegram.bot_username');
@endphp

<div class="min-h-screen bg-[#F2EBDD] text-[#1A1814]">
    <x-tools-header theme="cream" />

    <div class="px-4 py-8 md:py-12">
        {{-- Editorial breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="mx-auto flex max-w-7xl items-center gap-2 font-mono text-[11px] uppercase tracking-[0.14em] text-[#6E665C] lg:px-8">
            <a href="/" aria-label="Home" class="inline-flex items-center transition hover:text-[#1A1814]">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="size-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('tools.index') }}" class="transition hover:text-[#1A1814]">Tools</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="text-[#1A1814]">Telegram Health Logger</span>
        </nav>

        {{-- Hero --}}
        <header class="mx-auto mt-6 max-w-7xl lg:px-8">
            <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                <span class="size-1.5 rounded-full bg-[#C4623A] shadow-[0_0_0_3px_rgba(196,98,58,0.18)]" aria-hidden="true"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">AI-Powered Health Tracking</span>
            </div>

            <h1 class="mt-5 max-w-5xl text-balance font-bold text-[clamp(40px,5vw,68px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                Quick Health Logging with Telegram
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                Log your health data hands-free using AI-powered natural language. Open a chat, type "glucose 140" — done.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a
                    href="https://t.me/{{ $botUsername }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-none bg-[#1A1814] px-6 text-sm font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833]"
                >
                    Open {{ '@'.$botUsername }} on Telegram
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">Free · No app install · Type like you talk</span>
            </div>
        </header>

        {{-- How to Connect --}}
        <section class="mx-auto mt-20 max-w-7xl lg:px-8">
            <div class="grid gap-10 sm:grid-cols-[1fr_2fr] sm:gap-14">
                <div class="sm:sticky sm:top-28 sm:self-start">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Step-by-step</p>
                    <h2 class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        How to Connect
                    </h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-[#3D3833]">
                        Four steps, about a minute each. You'll be logging glucose with a sentence by step four.
                    </p>
                </div>

                <div>
                    <ol class="border-t border-[#D9CFBC]">
                        <li class="grid grid-cols-[auto_1fr] gap-6 border-b border-[#D9CFBC] py-6 sm:gap-10 sm:py-8">
                            <span class="pt-1 font-bold text-2xl italic leading-none text-[#C4623A]" aria-hidden="true">01</span>
                            <div>
                                <h3 class="font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Open Telegram</h3>
                                <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">
                                    Search for <strong class="font-bold text-[#1A1814]">{{ '@'.$botUsername }}</strong> or
                                    <a href="https://t.me/{{ $botUsername }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[#C4623A] underline decoration-[#C4623A]/40 underline-offset-4 transition hover:decoration-[#C4623A]">tap here to open the chat</a>.
                                </p>
                            </div>
                        </li>
                        <li class="grid grid-cols-[auto_1fr] gap-6 border-b border-[#D9CFBC] py-6 sm:gap-10 sm:py-8">
                            <span class="pt-1 font-bold text-2xl italic leading-none text-[#C4623A]" aria-hidden="true">02</span>
                            <div>
                                <h3 class="font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Start the bot</h3>
                                <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">
                                    Tap <strong class="font-bold text-[#1A1814]">Start</strong> or send <code class="border border-[#D9CFBC] bg-[#EBE2D0] px-1.5 py-0.5 font-mono text-[11px] text-[#1A1814]">/start</code> to see the welcome and setup instructions.
                                </p>
                            </div>
                        </li>
                    </ol>

                    <figure class="mt-6">
                        <div class="mx-auto max-w-xs border border-[#D9CFBC] bg-[#F2EBDD] p-2 sm:max-w-sm">
                            <img
                                src="{{ asset('screenshots/telegram-bot-welcome-screen.webp') }}"
                                alt="Telegram bot welcome screen showing @AcaraPlate_bot with Start button"
                                class="w-full"
                                width="600"
                                height="auto"
                                loading="lazy"
                            >
                        </div>
                        <figcaption class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                            Tap "Start" to begin using the bot
                        </figcaption>
                    </figure>

                    <ol class="mt-6 border-t border-[#D9CFBC]" start="3">
                        <li class="grid grid-cols-[auto_1fr] gap-6 border-b border-[#D9CFBC] py-6 sm:gap-10 sm:py-8">
                            <span class="pt-1 font-bold text-2xl italic leading-none text-[#C4623A]" aria-hidden="true">03</span>
                            <div>
                                <h3 class="font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Link your account</h3>
                                <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">
                                    Head to <a href="{{ route('integrations.edit') }}" class="font-medium text-[#C4623A] underline decoration-[#C4623A]/40 underline-offset-4 transition hover:decoration-[#C4623A]">Settings → Integrations</a> and generate a one-time linking token.
                                </p>
                            </div>
                        </li>
                        <li class="grid grid-cols-[auto_1fr] gap-6 border-b border-[#D9CFBC] py-6 sm:gap-10 sm:py-8">
                            <span class="pt-1 font-bold text-2xl italic leading-none text-[#C4623A]" aria-hidden="true">04</span>
                            <div>
                                <h3 class="font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">Start chatting</h3>
                                <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">
                                    Send <code class="border border-[#D9CFBC] bg-[#EBE2D0] px-1.5 py-0.5 font-mono text-[11px] text-[#1A1814]">/link YOUR_TOKEN</code> to connect — then just type a sentence to log anything.
                                </p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-8">
                        @auth
                            <a
                                href="{{ route('integrations.edit') }}"
                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                            >
                                Generate linking token
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                            >
                                Create free account
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                            <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">
                                Free · No credit card · Takes 30 seconds
                            </p>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        {{-- What you can log --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">What you can log</p>
            <h2 class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                Just describe your data naturally.
            </h2>
            <p class="mt-5 max-w-3xl text-base leading-relaxed text-[#3D3833]">
                The AI parses six categories from plain English. No forms, no menus, no special syntax — write the sentence the way you'd say it.
            </p>

            <div class="mt-10 grid gap-10 lg:grid-cols-[1.6fr_1fr] lg:items-start lg:gap-16">
                {{-- Bento: 6 categories in a 2-col mini-grid --}}
                <div class="grid border-t border-[#D9CFBC] sm:grid-cols-2 sm:divide-x sm:divide-[#D9CFBC]">
                    @foreach ($this->loggingExamples as $example)
                        <article class="border-b border-[#D9CFBC] px-2 py-7 last:border-b-0 sm:px-6 sm:py-8 sm:[&:nth-last-child(-n+2)]:border-b-0">
                            <div class="flex items-baseline gap-3">
                                <span class="text-2xl leading-none" aria-hidden="true">{{ $example['icon'] }}</span>
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">{{ $example['type'] }}</p>
                            </div>
                            <p class="mt-3 font-bold text-lg italic leading-snug tracking-[-0.01em] text-[#1A1814]">
                                &ldquo;{{ $example['examples'][0] }}&rdquo;
                            </p>
                            <ul class="mt-3 space-y-1.5">
                                @foreach (array_slice($example['examples'], 1) as $ex)
                                    <li class="font-mono text-[11px] uppercase tracking-[0.14em] text-[#6E665C]">
                                        or &ldquo;{{ $ex }}&rdquo;
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>

                {{-- Phone screenshot: visual companion, sticky on desktop --}}
                <figure class="lg:sticky lg:top-28">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A] lg:hidden">In the wild</p>
                    <p class="hidden font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A] lg:block">Live preview</p>
                    <div class="mx-auto mt-3 max-w-[18rem] border border-[#D9CFBC] bg-[#F2EBDD] p-2 sm:max-w-xs lg:mx-0 lg:max-w-none">
                        <img
                            src="{{ asset('screenshots/telegram-bot-logging-glucose.webp') }}"
                            alt="Telegram conversation showing user typing 'My glucose is 140' and bot responding with parsed glucose data"
                            class="w-full"
                            width="600"
                            height="auto"
                            loading="lazy"
                        >
                    </div>
                    <figcaption class="mt-3 max-w-xs font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C] lg:max-w-none">
                        Type naturally — the AI understands what you're logging
                    </figcaption>
                </figure>
            </div>
        </section>

        {{-- Tip --}}
        <section class="mx-auto mt-16 max-w-7xl lg:px-8">
            <blockquote class="border-l-4 border-[#C4623A] py-2 pl-6">
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#C4623A]">Tip</p>
                <p class="mt-2 max-w-2xl text-base leading-relaxed text-[#1A1814]">
                    You don't need commands to log data. Just send the message — the AI will figure it out.
                </p>
            </blockquote>
        </section>

        {{-- Available Commands --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="grid gap-10 sm:grid-cols-[1fr_2fr] sm:gap-14">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Reference</p>
                    <h2 class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        Available Commands
                    </h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-[#3D3833]">
                        Optional shortcuts. Most of the time, just type a sentence — but these come in handy.
                    </p>
                </div>

                <dl class="border-t border-[#D9CFBC]">
                    @foreach ($this->commands as $cmd)
                        <div class="grid grid-cols-[auto_1fr] gap-6 border-b border-[#D9CFBC] py-4 sm:gap-10">
                            <dt class="font-mono text-sm font-bold text-[#C4623A]">{{ $cmd['command'] }}</dt>
                            <dd class="text-sm leading-relaxed text-[#3D3833]">{{ $cmd['description'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- Field manual / definition --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="grid gap-6 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[1fr_2fr] sm:gap-12">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Field manual</p>
                </div>
                <p class="max-w-3xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                    <strong class="font-bold text-[#1A1814]">Plate on Telegram</strong> turns the chat app you already have into a hands-free health journal. Send a sentence about glucose, food, insulin, vitals, or movement — the bot parses it, asks for one-tap confirmation, and saves it to your private Plate account. No new app, no glove-fingers fumbling with forms.
                </p>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="mx-auto mt-16 max-w-7xl lg:px-8" aria-labelledby="how-it-works-heading">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">How it works</p>
            <h2 id="how-it-works-heading" class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                Why it feels effortless.
            </h2>

            <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-2 sm:divide-x sm:divide-[#D9CFBC] lg:grid-cols-4">
                @foreach ([
                    ['letter' => 'A', 'title' => 'Text Like You Talk', 'desc' => 'Forget complex forms. Just send a message like "Apple for snack" or "Blood pressure 120/80". Your AI assistant handles the rest.'],
                    ['letter' => 'B', 'title' => 'Instant Verification', 'desc' => 'Get immediate feedback on what you logged. A quick tap confirms it—keeping your data clean and accurate.'],
                    ['letter' => 'C', 'title' => 'Any Unit, Any Time', 'desc' => 'mg/dL or mmol/L? lbs or kg? Use whatever units you prefer. We\'ll convert and standardize them automatically.'],
                    ['letter' => 'D', 'title' => 'Global & Accessible', 'desc' => 'Log in English, Spanish, French, or your native tongue. Health tracking that speaks your language.'],
                ] as $step)
                    <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:px-7 lg:[&:nth-last-child(-n+4)]:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 sm:[&:nth-last-child(-n+2)]:lg:border-b-0">
                        <div class="font-bold text-5xl italic leading-none text-[#C4623A]">{{ $step['letter'] }}</div>
                        <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">{{ $step['title'] }}</h3>
                        <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">{{ $step['desc'] }}</p>
                    </article>
                @endforeach
            </div>

            <figure class="mt-12">
                <div class="mx-auto max-w-xs border border-[#D9CFBC] bg-[#F2EBDD] p-2 sm:max-w-md">
                    <img
                        src="{{ asset('screenshots/telegram-bot-confirmation-prompt.webp') }}"
                        alt="Telegram bot confirmation prompt showing 'Log: Glucose 140 mg/dL (random) - Reply /yes to confirm or /no to cancel'"
                        class="w-full"
                        width="600"
                        height="auto"
                        loading="lazy"
                    >
                </div>
                <figcaption class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                    The bot confirms before saving your health data
                </figcaption>
            </figure>
        </section>

        {{-- FAQ --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8" aria-labelledby="faq-heading">
            <div class="grid gap-12 sm:grid-cols-[1fr_2fr]">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">FAQ</p>
                    <h2 id="faq-heading" class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-[#3D3833]">
                        Quick answers about logging glucose, insulin, carbs, and more from a Telegram chat.
                    </p>
                </div>

                <div x-data="{ openFaq: 1 }">
                    @php
                    $faqs = [
                        ['q' => 'How do I log glucose on Telegram?', 'a' => 'Simply send a message like "My glucose is 140" or "Fasting glucose 95". The AI automatically detects it\'s glucose data, shows you the reading, and asks for confirmation. Reply /yes to save it to your health log.', 'speakable' => 'speakable-intro'],
                        ['q' => 'Can I log insulin via Telegram?', 'a' => 'Yes! Just say "Took 5 units of insulin" or "Bolus 3 units". You can specify insulin type (basal, bolus, or mixed) and the bot will log it with your health data.', 'speakable' => 'speakable-how-it-works'],
                        ['q' => 'What health data can I track on Telegram?', 'a' => 'You can log glucose (mg/dL or mmol/L), food and carbs (grams), insulin (units), medication (name and dosage), vitals (weight, blood pressure), and exercise (type and duration).'],
                        ['q' => 'Is Telegram secure for health data?', 'a' => 'Your Telegram account links securely to your Plate account using a unique token. Health data is stored in your private Plate account, not on Telegram\'s servers. Telegram\'s end-to-end encryption protects your conversations.'],
                        ['q' => 'Does the bot understand different units?', 'a' => 'Yes! The AI automatically converts units. Say "glucose 6.5" (mmol/L) or "glucose 140" (mg/dL)—it knows the difference. Say "weight 180 lbs" and it saves in kilograms. No need to do the math yourself.'],
                        ['q' => 'Can I ask nutrition questions too?', 'a' => 'Absolutely! Your AI nutritionist is available 24/7. Ask questions like "What should I eat for breakfast?" or "Will pizza spike my glucose?" or "I\'m at Chipotle, what should I order?"'],
                    ];
                    @endphp

                    @foreach ($faqs as $index => $faq)
                        @php $position = $index + 1; @endphp
                        <div class="border-t {{ $loop->first ? 'border-[#1A1814]' : 'border-[#D9CFBC]' }} {{ $loop->last ? 'border-b border-[#D9CFBC]' : '' }}">
                            <button
                                type="button"
                                @click="openFaq = openFaq === {{ $position }} ? null : {{ $position }}"
                                :aria-expanded="openFaq === {{ $position }} ? 'true' : 'false'"
                                class="flex w-full items-baseline justify-between gap-4 py-5 text-left transition hover:text-[#1A1814]"
                            >
                                <div class="flex items-baseline gap-4">
                                    <span class="font-mono text-[11px] tracking-[0.14em] text-[#6E665C]" aria-hidden="true">
                                        {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814] sm:text-xl {{ $faq['speakable'] ?? '' }}">
                                        {{ $faq['q'] }}
                                    </span>
                                </div>
                                <svg
                                    class="mt-1 size-5 shrink-0 text-[#C4623A] transition-transform duration-200"
                                    :class="{ 'rotate-45': openFaq === {{ $position }} }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <div x-show="openFaq === {{ $position }}" x-collapse class="overflow-hidden">
                                <p class="mb-6 max-w-prose pl-10 text-sm leading-relaxed text-[#3D3833] {{ $faq['speakable'] ?? '' }}">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- More tools strip --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <a
                href="{{ route('tools.index') }}"
                class="group flex flex-col gap-4 border-t border-[#1A1814] pt-8 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Explore More Free Tools</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.01em] text-[#1A1814] transition-colors group-hover:text-[#C4623A]">
                        View all free tools →
                    </h2>
                </div>
                <span class="font-mono text-[11px] uppercase tracking-[0.16em] text-[#6E665C]">
                    Calculators · Trackers · Planners
                </span>
            </a>
        </section>
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
