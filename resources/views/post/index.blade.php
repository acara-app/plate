@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', __('post.meta_keywords'))
@section('og_image_alt', __('post.og_image_alt'))
@section('canonical_url', $canonicalUrl)

@php
    $ogLocale = match ($locale) {
        'mn' => 'mn_MN',
        default => 'en_US',
    };
    $localeToOg = ['en' => 'en_US', 'mn' => 'mn_MN', 'fr' => 'fr_FR'];
    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG;

    $collectionSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $pageTitle,
        'description' => $pageDescription,
        'url' => $canonicalUrl,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Acara Plate',
            'url' => url('/'),
        ],
    ];

    $itemListSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $pageTitle,
        'numberOfItems' => $posts->total(),
        'itemListElement' => $posts->values()->map(fn ($post, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1 + (($posts->currentPage() - 1) * $posts->perPage()),
            'url' => $post->locale === 'en' ? route('post.show', $post->slug) : route('post.locale.show', ['locale' => $post->locale, 'slug' => $post->slug]),
            'name' => $post->display_name,
        ])->all(),
    ];
@endphp

@section('og_locale', $ogLocale)

@section('head')
    <script type="application/ld+json">
{!! json_encode($collectionSchema, $jsonFlags) !!}
</script>
    <script type="application/ld+json">
{!! json_encode($itemListSchema, $jsonFlags) !!}
</script>

    {{-- Pagination SEO links --}}
    @if($posts->currentPage() > 1)
    <link rel="prev" href="{{ $posts->previousPageUrl() }}" />
    @endif
    @if($posts->hasMorePages())
    <link rel="next" href="{{ $posts->nextPageUrl() }}" />
    @endif

    {{-- og:locale:alternate for social sharing --}}
    @foreach($hreflangLinks as $link)
        @if($link['locale'] !== $locale)
            <meta property="og:locale:alternate" content="{{ $localeToOg[$link['locale']] ?? $link['locale'] }}" />
        @endif
    @endforeach

    {{-- hreflang alternate links for multilingual SEO --}}
    @foreach($hreflangLinks as $link)
        <link rel="alternate" hreflang="{{ $link['locale'] }}" href="{{ $link['url'] }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}" />
@endsection

<x-default-layout>
    @include('post._header')

    <div class="relative bg-[#f7f3ed]">
        {{-- Paper grain texture --}}
        <x-paper-grain class="z-0" />

        <div class="relative z-10 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

            {{-- Hero / Featured Section --}}
            @if($posts->isNotEmpty())
                @php
                    $firstPost = $posts->first();
                    $firstExcerpt = $firstPost->body['excerpt'] ?? '';
                    $firstReadingTime = $firstPost->body['reading_time'] ?? null;
                    $firstCategoryLabel = $firstPost->category?->label() ?? '';
                    $firstPostUrl = $firstPost->locale === 'en' ? route('post.show', $firstPost->slug) : route('post.locale.show', ['locale' => $firstPost->locale, 'slug' => $firstPost->slug]);
                @endphp

                <a href="{{ $firstPostUrl }}" class="group block pt-12 sm:pt-16 pb-14 sm:pb-20">
                    <div class="relative overflow-hidden bg-white shadow-[0_4px_32px_-8px_rgba(26,26,26,0.12)] transition-all duration-500 group-hover:shadow-[0_12px_48px_-12px_rgba(26,26,26,0.22)]"
                         style="border-radius: 2px 40px 2px 40px;">
                        <div class="grid lg:grid-cols-2">
                            @if($firstPost->image_url)
                                <div class="aspect-[16/10] overflow-hidden bg-[#e8e4de] lg:aspect-auto lg:h-full">
                                    <img
                                        src="{{ $firstPost->image_url }}"
                                        alt="{{ $firstPost->display_name }}"
                                        class="h-full w-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-[1.03] transition-all duration-700 ease-out"
                                        loading="eager"
                                    />
                                </div>
                            @else
                                <div class="aspect-[16/10] lg:aspect-auto lg:h-full bg-gradient-to-br from-[#1b4332]/10 via-[#e8e4de] to-[#1b4332]/5 flex items-center justify-center">
                                    <svg class="size-20 text-[#1a1a1a]/10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                            @endif

                            <div class="relative flex flex-col justify-center p-8 sm:p-10 lg:p-12">
                                {{-- Decorative top line --}}
                                <div class="absolute top-0 left-8 right-8 lg:left-12 lg:right-12 h-px bg-gradient-to-r from-transparent via-[#bc4749]/30 to-transparent"></div>

                                @if($firstCategoryLabel)
                                    <div class="flex items-center gap-3 mb-5">
                                        <span class="h-px w-8 bg-[#bc4749]"></span>
                                        <span class="font-mono text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                                            {{ $firstCategoryLabel }}
                                        </span>
                                    </div>
                                @endif

                                <h1 class="font-display text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[#1a1a1a] mb-5 leading-[1.1]">
                                    <x-editorial-underline>{{ $firstPost->display_name }}</x-editorial-underline>
                                </h1>

                                @if($firstExcerpt)
                                    <p class="text-[#1a1a1a]/60 text-base sm:text-lg leading-relaxed mb-8 max-w-lg font-serif">
                                        {{ $firstExcerpt }}
                                    </p>
                                @endif

                                <div class="flex items-center gap-4 text-xs text-[#1a1a1a]/40 font-mono">
                                    @if($firstReadingTime)
                                        <span class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ __('post.min_read', ['minutes' => $firstReadingTime]) }}
                                        </span>
                                        <span class="text-[#1a1a1a]/15" aria-hidden="true">|</span>
                                    @endif
                                    <time datetime="{{ $firstPost->created_at->toIso8601String() }}">
                                        {{ $firstPost->created_at->format('M j, Y') }}
                                    </time>
                                </div>

                                <div class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-[#1b4332] transition-all duration-300 group-hover:gap-3">
                                    <x-editorial-underline>{{ __('post.read_article') }}</x-editorial-underline>
                                    <svg class="size-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            @else
                <div class="pt-16 pb-12">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="h-px w-12 bg-[#bc4749]"></span>
                        <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                            Journal
                        </span>
                    </div>
                    <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-[#1a1a1a] mb-4">
                        {{ $pageTitle }}
                    </h1>
                    <p class="text-lg text-[#1a1a1a]/60 max-w-2xl leading-relaxed font-serif">
                        {{ $pageDescription }}
                    </p>
                </div>
            @endif

            {{-- Divider --}}
            @if($posts->isNotEmpty())
                @php $remainingPosts = $posts->skip(1); @endphp

                @if($remainingPosts->isNotEmpty())
                    <div class="flex items-center gap-6 mb-10">
                        <div class="h-px flex-1 bg-gradient-to-r from-[#1a1a1a]/20 to-transparent"></div>
                        <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#1a1a1a]/40 shrink-0">
                            Latest entries
                        </span>
                        <div class="h-px flex-1 bg-gradient-to-l from-[#1a1a1a]/20 to-transparent"></div>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-7 mb-14">
                        @foreach($remainingPosts as $post)
                            @include('post._card', ['post' => $post])
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-center py-24">
                    <div class="mx-auto size-20 overflow-hidden bg-[#e8e4de] flex items-center justify-center mb-6" style="border-radius: 2px 20px 2px 20px;">
                        <svg class="size-10 text-[#1a1a1a]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-xl font-bold text-[#1a1a1a] mb-2">{{ __('post.no_articles_title') }}</h3>
                    <p class="text-[#1a1a1a]/50 max-w-sm mx-auto font-serif">
                        {{ __('post.no_articles_description') }}
                    </p>
                </div>
            @endif

            {{-- Pagination --}}
            @if ($posts->hasPages())
                <div class="mt-8 mb-14">
                    {{ $posts->links() }}
                </div>
            @endif

            {{-- CTA Section --}}
            <div class="pb-20">
                <div class="relative overflow-hidden bg-[#1b4332] p-10 sm:p-14 lg:p-16 shadow-[0_8px_32px_-8px_rgba(27,67,50,0.4)]"
                     style="border-radius: 2px 32px 2px 32px;">
                    <x-paper-grain class="opacity-50" />

                    {{-- Decorative botanical SVG --}}
                    <svg class="absolute top-4 right-4 w-24 h-24 opacity-[0.08] pointer-events-none" viewBox="0 0 120 120" fill="none" aria-hidden="true">
                        <path d="M60 10C60 10 20 40 20 80c0 22 17.9 40 40 40s40-18 40-40c0-40-40-70-40-70z" stroke="#f7f3ed" stroke-width="1.5" fill="none"/>
                        <path d="M60 30v80M60 55c-10-8-24-4-32 8M60 70c10-8 24-4 32 8" stroke="#f7f3ed" stroke-width="1" stroke-linecap="round" opacity="0.5"/>
                    </svg>

                    <div class="relative z-10 max-w-2xl">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="h-px w-8 bg-[#f7f3ed]/40"></span>
                            <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#f7f3ed]/60">
                                Get Started
                            </span>
                        </div>
                        <h2 class="font-display text-3xl sm:text-4xl font-bold text-[#f7f3ed] mb-4">
                            {{ __('post.cta_index_title') }}
                        </h2>
                        <p class="text-[#f7f3ed]/70 text-base sm:text-lg leading-relaxed mb-8 font-serif">
                            {{ __('post.cta_index_description') }}
                        </p>
                        <a href="{{ route('meet-altani') }}"
                           class="inline-flex items-center gap-2 rounded-full bg-[#f7f3ed] px-7 py-3.5 text-sm font-semibold text-[#1b4332] transition-all duration-300 hover:bg-white hover:gap-3">
                            {{ __('post.cta_index_button') }}
                            <svg class="size-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-footer />
</x-default-layout>
