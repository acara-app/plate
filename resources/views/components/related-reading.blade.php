@props([
    'eyebrow' => 'From the Acara Blog',
    'title' => 'Keep Reading',
    'description' => 'Guides and product updates that explain how Acara turns health data into practical next steps.',
    'articles' => [],
])

@php
    $articles = collect($articles)->values();
    $featuredArticle = $articles->first();
    $supportingArticles = $articles->slice(1)->values();
@endphp

@if($articles->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-[#f7f3ed] py-20 sm:py-28 lg:py-32']) }}>
        {{-- Paper grain texture overlay --}}
        <x-paper-grain class="z-0" />

        {{-- Decorative botanical illustration SVG top-right --}}
        <svg class="absolute top-8 right-8 w-32 h-32 opacity-[0.06] pointer-events-none z-0" viewBox="0 0 120 120" fill="none" aria-hidden="true">
            <path d="M60 10C60 10 20 40 20 80c0 22 17.9 40 40 40s40-18 40-40c0-40-40-70-40-70z" stroke="#1b4332" stroke-width="1.5" fill="none"/>
            <path d="M60 30v80M60 55c-10-8-24-4-32 8M60 70c10-8 24-4 32 8" stroke="#1b4332" stroke-width="1" stroke-linecap="round" opacity="0.5"/>
            <circle cx="60" cy="50" r="3" fill="#bc4749" opacity="0.4"/>
            <circle cx="45" cy="70" r="2" fill="#bc4749" opacity="0.3"/>
            <circle cx="75" cy="75" r="2.5" fill="#bc4749" opacity="0.3"/>
        </svg>

        {{-- Decorative line element bottom-left --}}
        <svg class="absolute bottom-16 left-0 w-48 h-1 opacity-[0.15] pointer-events-none z-0" aria-hidden="true">
            <line x1="0" y1="0.5" x2="192" y2="0.5" stroke="#1b4332" stroke-width="1" stroke-dasharray="4 6"/>
        </svg>

        <div class="relative z-10 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            {{-- Section Header --}}
            <div class="mb-14 sm:mb-18">
                <div class="flex items-center gap-4 mb-6">
                    <span class="h-px w-12 bg-[#bc4749]"></span>
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                        {{ $eyebrow }}
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <h2 class="font-display text-4xl font-bold tracking-tight text-[#1a1a1a] sm:text-5xl lg:text-[3.5rem] lg:leading-[1.1]">
                        {{ $title }}
                    </h2>
                    <p class="max-w-xl text-lg leading-relaxed text-[#1a1a1a]/70 font-serif lg:justify-self-end">
                        {{ $description }}
                    </p>
                </div>
            </div>

            {{-- Editorial Grid Layout --}}
            <div class="mt-10 grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                {{-- Featured Article --}}
                <a
                    href="{{ $featuredArticle['url'] }}"
                    class="group relative flex flex-col overflow-hidden bg-white shadow-[0_4px_24px_-8px_rgba(26,26,26,0.12)] transition-all duration-500 hover:shadow-[0_12px_40px_-12px_rgba(26,26,26,0.2)]"
                    style="border-radius: 2px 32px 2px 32px;"
                >
                    @isset($featuredArticle['image'])
                        <div class="aspect-[16/10] overflow-hidden bg-[#e8e4de]">
                            <img
                                src="{{ $featuredArticle['image'] }}"
                                alt="{{ $featuredArticle['imageAlt'] ?? $featuredArticle['title'] }}"
                                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                                loading="lazy"
                            >
                        </div>
                    @endisset

                    <div class="relative flex flex-col p-7 sm:p-9">
                        {{-- Hand-drawn accent line --}}
                        <div class="absolute top-0 left-8 right-8 h-px bg-gradient-to-r from-transparent via-[#bc4749]/30 to-transparent"></div>

                        <div class="flex flex-wrap items-center gap-3 text-[0.7rem] font-mono font-semibold uppercase tracking-[0.15em] text-[#bc4749]">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#bc4749]"></span>
                                {{ $featuredArticle['category'] ?? 'Product Update' }}
                            </span>
                            @isset($featuredArticle['readingTime'])
                                <span class="text-[#1a1a1a]/20" aria-hidden="true">|</span>
                                <span class="text-[#1a1a1a]/50">{{ $featuredArticle['readingTime'] }}</span>
                            @endisset
                        </div>

                        <h3 class="mt-5 font-display text-2xl font-bold leading-tight text-[#1a1a1a] sm:text-3xl">
                            <x-editorial-underline>{{ $featuredArticle['title'] }}</x-editorial-underline>
                        </h3>

                        <p class="mt-4 text-base leading-7 text-[#1a1a1a]/60 font-serif">
                            {{ $featuredArticle['description'] }}
                        </p>

                        <span class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-[#1b4332] transition-all duration-300 group-hover:gap-3">
                            <x-editorial-underline>Read the feature</x-editorial-underline>
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </span>
                    </div>
                </a>

                {{-- Supporting Articles --}}
                @if($supportingArticles->isNotEmpty())
                    <div class="flex flex-col">
                        <div class="flex items-center justify-between gap-4 border-b-2 border-[#1a1a1a]/10 pb-4 mb-6">
                            <h3 class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-[#1a1a1a]/60">
                                More Reading
                            </h3>
                            <span class="font-mono text-xs font-medium text-[#1a1a1a]/40">
                                {{ str_pad((string) $supportingArticles->count(), 2, '0', STR_PAD_LEFT) }} articles
                            </span>
                        </div>

                        <div class="flex flex-col gap-5">
                            @foreach($supportingArticles as $article)
                                <a
                                    href="{{ $article['url'] }}"
                                    class="group relative flex gap-5 rounded-lg p-4 transition-all duration-300 hover:bg-white hover:shadow-[0_4px_20px_-8px_rgba(26,26,26,0.1)]"
                                >
                                    @isset($article['image'])
                                        <div class="shrink-0 w-20 h-20 overflow-hidden bg-[#e8e4de] sm:w-24 sm:h-24" style="border-radius: 2px 16px 2px 16px;">
                                            <img
                                                src="{{ $article['image'] }}"
                                                alt="{{ $article['imageAlt'] ?? $article['title'] }}"
                                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.06]"
                                                loading="lazy"
                                            >
                                        </div>
                                    @endisset

                                    <div class="flex min-w-0 flex-col justify-center">
                                        <div class="flex flex-wrap items-center gap-2 text-[0.65rem] font-mono font-semibold uppercase tracking-[0.12em] text-[#bc4749]">
                                            <span>{{ $article['category'] ?? 'Product Update' }}</span>
                                            @isset($article['readingTime'])
                                                <span class="text-[#1a1a1a]/15" aria-hidden="true">|</span>
                                                <span class="text-[#1a1a1a]/40">{{ $article['readingTime'] }}</span>
                                            @endisset
                                        </div>

                                        <h4 class="mt-2 font-display text-base font-bold leading-snug text-[#1a1a1a] sm:text-lg">
                                            <x-editorial-underline>{{ $article['title'] }}</x-editorial-underline>
                                        </h4>

                                        <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-[#1a1a1a]/50 font-serif hidden sm:block">
                                            {{ $article['description'] }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Archive CTA --}}
                    <a
                        href="{{ route('post.index') }}"
                        class="group relative flex min-h-[22rem] flex-col justify-between overflow-hidden border-2 border-dashed border-[#1a1a1a]/15 bg-[#1b4332]/[0.03] p-8 transition-all duration-300 hover:border-[#1b4332]/30 hover:bg-[#1b4332]/[0.06]"
                        style="border-radius: 2px 24px 2px 24px;"
                    >
                        <div>
                            <div class="flex items-center gap-3 mb-5">
                                <span class="h-px w-8 bg-[#bc4749]"></span>
                                <p class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-[#bc4749]">
                                    Blog archive
                                </p>
                            </div>
                            <p class="font-display text-2xl font-bold leading-snug text-[#1a1a1a]">
                                Browse every guide, launch note, and nutrition explainer from Acara.
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-2 text-sm font-semibold text-[#1b4332] transition-all duration-300 group-hover:gap-3">
                            <x-editorial-underline>View all posts</x-editorial-underline>
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif
