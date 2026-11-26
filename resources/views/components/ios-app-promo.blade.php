@props([
    'eyebrow' => 'Now on iPhone',
    'headline' => 'Sync Apple Health automatically',
    'body' => 'Acara Health Sync reads your Apple Health data — glucose, sleep, activity, and 100+ other health types — encrypts it on your phone, and sends it straight to your Plate dashboard. No manual logging, no cloud middleman.',
    'features' => [
        'End-to-end encrypted',
        '100+ health types',
        'Free & open source',
    ],
    'secondaryLinkUrl' => null,
    'secondaryLinkText' => 'See how it works',
])

<section aria-labelledby="ios-app-promo-heading" class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-linear-to-br from-emerald-50 via-white to-white p-8 shadow-sm sm:p-10 dark:border-emerald-900/40 dark:from-emerald-950/30 dark:via-slate-950 dark:to-slate-950">
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-emerald-400/10 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-12 -bottom-12 h-48 w-48 rounded-full bg-emerald-300/10 blur-2xl"></div>

        <div class="relative grid gap-8 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <span class="relative flex h-2 w-2" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    {{ $eyebrow }}
                </span>

                <h2 id="ios-app-promo-heading" class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-white">
                    {{ $headline }}
                </h2>

                <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $body }}
                </p>

                @if (! empty($features))
                    <ul role="list" class="mt-6 grid gap-2 sm:grid-cols-2">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="flex flex-col items-start gap-3 lg:col-span-2 lg:items-end">
                <x-app-store-badge size="lg" />
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Requires iOS {{ config('acara.health_sync.minimum_ios_version') }} or later. Free.
                </p>
                @if ($secondaryLinkUrl)
                    <a href="{{ $secondaryLinkUrl }}" class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                        {{ $secondaryLinkText }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
