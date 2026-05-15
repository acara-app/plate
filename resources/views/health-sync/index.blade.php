@section('title', 'Acara Health Sync — Apple Health to Your Plate Dashboard | Now on the App Store')
@section('meta_description', 'Now on the App Store. Acara Health Sync pipes your Apple Health data into Plate with AES-256-GCM end-to-end encryption — glucose, weight, sleep, activity, and 100+ other types, all automatic.')
@section('meta_keywords', 'apple health sync, ios health companion, encrypted health data, health data sync, glucose sync, acara plate')

@section('head')
    <x-json-ld.health-sync />
@endsection

<x-default-layout>
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
                <span aria-current="page" class="text-[#1A1814]">Health Sync</span>
            </nav>

            {{-- Hero --}}
            <header class="mx-auto mt-6 max-w-7xl lg:px-8">
                <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                    <span class="relative flex size-1.5" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#C4623A] opacity-75"></span>
                        <span class="relative inline-flex size-1.5 rounded-full bg-[#C4623A]"></span>
                    </span>
                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">Now on the App Store</span>
                </div>

                <h1 class="speakable-intro mt-5 max-w-5xl text-balance font-bold text-[clamp(40px,5vw,72px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                    Stop reading your health data.<br class="hidden sm:block"> Start analyzing it.
                </h1>
                <p class="speakable-intro mt-4 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                    Acara Health Sync pipes every HealthKit metric into your Plate instance — encrypted on your phone, decrypted only by you.
                </p>

                {{-- Trust chips --}}
                <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 font-mono text-[11px] uppercase tracking-[0.16em] text-[#6E665C]">
                    <li class="inline-flex items-center gap-2">
                        <span class="size-1.5 bg-[#C4623A]" aria-hidden="true"></span>
                        End-to-End Encrypted
                    </li>
                    <li class="inline-flex items-center gap-2">
                        <span class="size-1.5 bg-[#C4623A]" aria-hidden="true"></span>
                        100+ health types
                    </li>
                    <li class="inline-flex items-center gap-2">
                        <span class="size-1.5 bg-[#C4623A]" aria-hidden="true"></span>
                        Open source
                    </li>
                </ul>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-app-store-badge size="lg" />
                    <a
                        href="{{ route('health-sync.setup') }}"
                        class="inline-flex h-12 items-center gap-2 rounded-none border border-[#1A1814] bg-transparent px-6 font-mono text-[11px] uppercase tracking-[0.16em] text-[#1A1814] transition hover:bg-[#1A1814] hover:text-[#F2EBDD]"
                    >
                        See the 5-minute setup
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
                <p class="mt-3 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                    Free · iPhone on iOS {{ config('plate.health_sync.minimum_ios_version') }} or later
                </p>
            </header>

            {{-- Data Flow Diagram --}}
            <section class="mx-auto mt-20 max-w-7xl lg:px-8" aria-labelledby="dataflow-heading">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">The path your data takes</p>
                <h2 id="dataflow-heading" class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                    Phone &rarr; encrypted tunnel &rarr; Acara.
                </h2>

                <div class="mt-8 grid gap-3 lg:grid-cols-[1fr_auto_1fr_auto_1fr] lg:items-stretch lg:gap-4">
                    {{-- Apple Health Node --}}
                    <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-5 sm:p-6">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Source</p>
                        <div class="mt-3 flex items-baseline gap-3">
                            <svg class="size-7 text-[#C4623A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            <p class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814]">Apple Health</p>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">Your iPhone's HealthKit store — the home for everything your Watch, scale, and CGM already feed in.</p>
                    </article>

                    {{-- Arrow 1 --}}
                    <div class="flex flex-row items-center justify-center gap-2 py-2 lg:flex-col lg:py-0">
                        <span class="h-px w-8 bg-[#C4623A] lg:hidden" aria-hidden="true"></span>
                        <span class="hidden h-8 w-px bg-[#C4623A] lg:block" aria-hidden="true"></span>
                        <span class="border border-[#C4623A] px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.16em] text-[#C4623A]">reads</span>
                        <span class="h-px w-8 bg-[#C4623A] lg:hidden" aria-hidden="true"></span>
                        <span class="hidden h-8 w-px bg-[#C4623A] lg:block" aria-hidden="true"></span>
                    </div>

                    {{-- Health Sync Node (highlighted) --}}
                    <article class="border-2 border-[#1A1814] bg-[#EBE2D0] p-5 sm:p-6">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Bridge</p>
                        <div class="mt-3 flex items-baseline gap-3">
                            <svg class="size-7 text-[#1A1814]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <p class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814]">Health Sync</p>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">A lightweight iOS app. Encrypts each sample with AES-256-GCM before it ever leaves the device.</p>
                    </article>

                    {{-- Arrow 2 --}}
                    <div class="flex flex-row items-center justify-center gap-2 py-2 lg:flex-col lg:py-0">
                        <span class="h-px w-8 bg-[#C4623A] lg:hidden" aria-hidden="true"></span>
                        <span class="hidden h-8 w-px bg-[#C4623A] lg:block" aria-hidden="true"></span>
                        <span class="border border-[#C4623A] px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.16em] text-[#C4623A]">sends</span>
                        <span class="h-px w-8 bg-[#C4623A] lg:hidden" aria-hidden="true"></span>
                        <span class="hidden h-8 w-px bg-[#C4623A] lg:block" aria-hidden="true"></span>
                    </div>

                    {{-- Plate Node --}}
                    <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-5 sm:p-6">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Destination</p>
                        <div class="mt-3 flex items-baseline gap-3">
                            <span class="text-2xl leading-none" role="img" aria-label="strawberry">🍓</span>
                            <p class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814]">Acara Plate</p>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">Your Plate instance decrypts each sample with your private key and stores it in your dashboard.</p>
                    </article>
                </div>
            </section>

            {{-- Why does this exist? --}}
            <section class="mx-auto mt-24 max-w-7xl lg:px-8">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Why this exists</p>
                <h2 class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                    Apple Health doesn't have an API. So we built the bridge.
                </h2>

                <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-3 sm:divide-x sm:divide-[#D9CFBC]">
                    @foreach ([
                        ['letter' => 'A', 'title' => 'Apple Health has no API', 'desc' => "Web apps can't talk to HealthKit. It's an iOS-only sandbox. That's a problem if your nutrition platform lives in a browser."],
                        ['letter' => 'B', 'title' => 'So we built a bridge', 'desc' => 'Health Sync is a lightweight iOS app that reads your HealthKit data and securely relays it to Plate. Think of it as a one-way encrypted tunnel.'],
                        ['letter' => 'C', 'title' => 'Smarter meal plans, automatically', 'desc' => "When Plate's AI has your real activity, sleep, glucose, and vitals, it stops guessing and starts personalizing. Better data means better plans."],
                    ] as $step)
                        <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 last:border-b-0 sm:border-b-0 sm:px-7">
                            <div class="font-bold text-5xl italic leading-none text-[#C4623A]">{{ $step['letter'] }}</div>
                            <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">{{ $step['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- What Can You Sync? --}}
            <section class="mx-auto mt-24 max-w-7xl lg:px-8">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">What you can sync</p>
                <h2 class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                    Eleven categories from Apple Health.
                </h2>
                <p class="mt-5 max-w-3xl text-base leading-relaxed text-[#3D3833]">
                    You pick exactly what to share — nothing syncs without your say-so.
                </p>

                @php
                    $syncCategories = [
                        ['name' => 'Glucose', 'detail' => 'Blood Glucose'],
                        ['name' => 'Vitals', 'detail' => 'Heart Rate, HRV, Blood Pressure, SpO2'],
                        ['name' => 'Body', 'detail' => 'Weight, BMI, Body Fat %, Height'],
                        ['name' => 'Activity', 'detail' => 'Steps, Active Energy, Exercise, Workouts'],
                        ['name' => 'Mobility', 'detail' => 'VO2 Max, Walking Speed, 6-Min Walk'],
                        ['name' => 'Sleep', 'detail' => 'Time in Bed, REM, Deep, Core Sleep'],
                        ['name' => 'Nutrition', 'detail' => 'Calories, Carbs, Protein, Fat, Fiber'],
                        ['name' => 'Reproductive Health', 'detail' => 'Menstrual Flow, Basal Temp, Ovulation'],
                        ['name' => 'Hearing', 'detail' => 'Environmental Audio, Headphone Levels'],
                        ['name' => 'Mindfulness', 'detail' => 'Mindful Minutes, Time in Daylight'],
                        ['name' => 'Medications', 'detail' => 'Medication Logs'],
                    ];
                @endphp

                <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-2 sm:divide-x sm:divide-[#D9CFBC] lg:grid-cols-3">
                    @foreach ($syncCategories as $i => $category)
                        <article class="border-b border-[#D9CFBC] px-2 py-6 sm:px-6 sm:py-7 {{ ($category['comingSoon'] ?? false) ? 'opacity-60' : '' }}">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">
                                    {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                                </p>
                                @if ($category['comingSoon'] ?? false)
                                    <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">Coming soon</span>
                                @endif
                            </div>
                            <h3 class="mt-2 font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814]">
                                {{ $category['name'] }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-[#3D3833]">{{ $category['detail'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Security --}}
            <section class="mx-auto mt-24 max-w-7xl lg:px-8">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Security</p>
                <h2 class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                    We took the paranoid approach.
                </h2>
                <p class="mt-5 max-w-3xl text-base leading-relaxed text-[#3D3833]">
                    Your health data is encrypted before it ever leaves your phone. Three layers, three different protections.
                </p>

                <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-3 sm:divide-x sm:divide-[#D9CFBC]">
                    @foreach ([
                        [
                            'kicker' => 'Layer 1',
                            'title' => 'End-to-end encryption',
                            'body' => 'Every sample is encrypted on your iPhone using <strong class="font-bold text-[#1A1814]">AES-256-GCM</strong> before it leaves the device. The server only sees ciphertext in transit.',
                        ],
                        [
                            'kicker' => 'Layer 2',
                            'title' => 'Hardware-level key storage',
                            'body' => 'Your API token and encryption key live in the <strong class="font-bold text-[#1A1814]">iOS Keychain</strong>, backed by the Secure Enclave — the same hardware vault that protects Face ID.',
                        ],
                        [
                            'kicker' => 'Layer 3',
                            'title' => 'One token, one device',
                            'body' => 'Each device gets its own <strong class="font-bold text-[#1A1814]">Sanctum API token</strong> with a single permission: push health data. Revoke it any time from Settings.',
                        ],
                    ] as $layer)
                        <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 last:border-b-0 sm:border-b-0 sm:px-7">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">{{ $layer['kicker'] }}</p>
                            <h3 class="mt-3 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">{{ $layer['title'] }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">{!! $layer['body'] !!}</p>
                        </article>
                    @endforeach
                </div>

                <ul class="mt-10 border-t border-[#D9CFBC]">
                    @foreach ([
                        'No third-party servers. No cloud relay. No analytics. Data goes straight from your phone to your Plate instance.',
                        'App-scoped Keychain. Other apps on your phone cannot access your credentials.',
                        'Disconnect from the app or the web. Your call, your data.',
                    ] as $i => $promise)
                        <li class="grid grid-cols-[auto_1fr] items-baseline gap-4 border-b border-[#D9CFBC] py-4 sm:gap-6">
                            <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]" aria-hidden="true">
                                {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <p class="text-sm leading-relaxed text-[#3D3833]">{{ $promise }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Open Source --}}
            <section class="mx-auto mt-24 max-w-7xl lg:px-8">
                <div class="grid gap-6 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[1fr_2fr] sm:gap-12">
                    <div>
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Open source</p>
                    </div>
                    <p class="max-w-3xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                        <strong class="font-bold text-[#1A1814]">Trust should be verifiable.</strong> Both the iOS app and the Plate backend are fully open source. You can read every line of encryption code, audit the data handling, and self-host the whole thing. No black boxes.
                    </p>
                </div>
            </section>

            {{-- Final CTA --}}
            <section class="mx-auto mt-16 max-w-7xl lg:px-8">
                <article class="border border-[#1A1814] bg-[#1A1814] p-8 text-[#F2EBDD] sm:p-12">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Get started</p>
                    <div class="mt-3 grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-center">
                        <div>
                            <h2 class="font-bold text-2xl leading-tight tracking-[-0.02em] sm:text-3xl">
                                Ready to stop typing in your glucose readings?
                            </h2>
                            <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#F2EBDD]/80 sm:text-base">
                                Install the app, scan one QR code, and your data flows automatically from that point on.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                            <x-app-store-badge size="lg" variant="outline" />
                            <a
                                href="{{ route('health-sync.setup') }}"
                                class="inline-flex h-12 items-center gap-2 rounded-none border border-[#C4623A] px-6 font-mono text-[11px] uppercase tracking-[0.16em] text-[#C4623A] transition hover:bg-[#C4623A] hover:text-[#F2EBDD]"
                            >
                                5-minute setup
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
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
</x-default-layout>
