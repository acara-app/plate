@props([
    'heading',
    'location',
    'headingTag' => 'h2',
    'cta' => 'Scan your meal free',
])

<section {{ $attributes->class('@container not-prose overflow-hidden rounded-3xl bg-black text-white') }}>
    <div class="grid gap-8 p-6 sm:p-10 @3xl:grid-cols-[minmax(0,1fr)_minmax(0,18rem)] @3xl:items-center @3xl:gap-12">
        <div>
            <p class="flex items-center gap-2 text-sm font-semibold text-primary">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h2l2-2h6l2 2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                    <circle cx="12" cy="12" r="4" />
                </svg>
                Snap to Track · free to try
            </p>
            <{{ $headingTag }} class="mt-3 text-2xl leading-tight font-bold tracking-tight text-balance text-white sm:text-3xl">{{ $heading }}</{{ $headingTag }}>
            <p class="mt-3 max-w-xl text-base leading-relaxed text-pretty text-white/75 sm:text-lg">{{ $slot }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-3">
                <a
                    href="{{ route('snap-to-track') }}"
                    data-umami-event="snap_to_track_cta_click"
                    data-umami-event-location="{{ $location }}"
                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-primary px-7 py-3 text-base font-semibold text-emerald-950 no-underline shadow-lg shadow-primary/20 transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
                >
                    {{ $cta }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <span class="text-sm text-white/55">No signup needed</span>
            </div>
        </div>
        <img
            src="{{ asset('health-log-hero-meal.webp') }}"
            width="1000"
            height="417"
            loading="lazy"
            decoding="async"
            alt="Example Snap to Track result: a chicken quinoa salad estimated at 606 kcal with protein, carbs, fat, and fiber"
            class="w-full max-w-md rounded-2xl bg-white"
        >
    </div>
</section>
