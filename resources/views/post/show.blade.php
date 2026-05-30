@section('title', $content->meta_title)
@section('meta_description', $content->meta_description)
@section('meta_keywords', __('post.meta_keywords'))
@section('og_type', 'article')
@section('og_image', $content->image_url ?? asset('banner-acara-plate.webp'))
@section('og_image_alt', $content->display_name)

@php
    $displayName = $content->display_name;
    $excerpt = $content->body['excerpt'] ?? '';
    $bodyContent = $content->body['content'] ?? '';
    $readingTime = $content->body['reading_time'] ?? null;
    $postUrl = $locale === 'en' ? route('post.show', $content->slug) : route('post.locale.show', ['locale' => $locale, 'slug' => $content->slug]);
    $ogLocale = match ($locale) {
        'mn' => 'mn_MN',
        default => 'en_US',
    };
    $localeToOg = ['en' => 'en_US', 'mn' => 'mn_MN', 'fr' => 'fr_FR'];
    $indexUrl = $locale === 'en' ? route('post.index') : route('post.locale.index', ['locale' => $locale]);

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $content->title,
        'description' => $content->meta_description,
        'image' => $content->image_url ?? asset('banner-acara-plate.webp'),
        'inLanguage' => $locale,
        'author' => [
            '@type' => 'Organization',
            'name' => 'Acara Plate',
            'url' => url('/'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Acara Plate',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('apple-touch-icon/apple-touch-icon-180x180.png'),
            ],
        ],
        'datePublished' => $content->created_at->toIso8601String(),
        'dateModified' => $content->updated_at->toIso8601String(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $postUrl,
        ],
    ];

    $breadcrumbItems = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => __('post.breadcrumb_home'), 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => __('post.page_title'), 'item' => $indexUrl],
    ];
    if ($content->category) {
        $categoryUrl = $locale === 'en'
            ? route('post.category', ['category' => $content->category->value])
            : route('post.locale.category', ['locale' => $locale, 'category' => $content->category->value]);
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $content->category->label(), 'item' => $categoryUrl];
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $content->title];
    } else {
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $content->title];
    }

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG;

    $webPageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $content->title,
        'url' => $postUrl,
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => ['.speakable-intro'],
        ],
    ];
@endphp

@section('canonical_url', $postUrl)
@section('og_locale', $ogLocale)

@section('head')
    {{-- Article Open Graph meta tags --}}
    <meta property="article:published_time" content="{{ $content->created_at->toIso8601String() }}" />
    <meta property="article:modified_time" content="{{ $content->updated_at->toIso8601String() }}" />
    @if($content->category)
    <meta property="article:section" content="{{ $content->category->label() }}" />
    @endif

    {{-- og:locale:alternate for social sharing --}}
    @foreach($translations as $translation)
        <meta property="og:locale:alternate" content="{{ $localeToOg[$translation->locale] ?? $translation->locale }}" />
    @endforeach

    {{-- hreflang alternate links for multilingual SEO --}}
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $postUrl }}" />
    @foreach($translations as $translation)
        <link rel="alternate" hreflang="{{ $translation->locale }}" href="{{ $translation->locale === 'en' ? route('post.show', $translation->slug) : route('post.locale.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}" />

    <script type="application/ld+json">
{!! json_encode($articleSchema, $jsonFlags) !!}
</script>

    <script type="application/ld+json">
{!! json_encode($breadcrumbSchema, $jsonFlags) !!}
</script>

    {{-- Language switcher for search engines --}}
    @if($translations->isNotEmpty())
    <script type="application/ld+json">
{!! json_encode($webPageSchema, $jsonFlags) !!}
</script>
    @endif
@endsection

<x-default-layout>
    @include('post._header')

    <div class="relative bg-[#f7f3ed]">
        {{-- Paper grain texture --}}
        <x-paper-grain class="z-0" />

        <div class="relative z-10 mx-auto max-w-3xl px-6 lg:px-8">
            <article class="pt-10 pb-16">
                {{-- Breadcrumb-style back link --}}
                <a href="{{ $indexUrl }}" class="group inline-flex items-center gap-2 mb-10 text-sm font-mono text-[#1a1a1a]/40 hover:text-[#1b4332] transition-colors duration-200">
                    <svg class="size-4 transition-transform duration-200 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <x-editorial-underline>Back to journal</x-editorial-underline>
                </a>

                {{-- Category & Reading Time --}}
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    @if($content->category)
                        <div class="flex items-center gap-2">
                            <span class="h-px w-6 bg-[#bc4749]"></span>
                            <span class="inline-flex items-center gap-1.5 font-mono text-[0.7rem] font-semibold uppercase tracking-[0.15em] text-[#bc4749]">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#bc4749]"></span>
                                {{ $content->category->label() }}
                            </span>
                        </div>
                    @endif
                    @if($readingTime)
                        <span class="text-xs text-[#1a1a1a]/30 font-mono flex items-center gap-1.5">
                            <span class="text-[#1a1a1a]/15" aria-hidden="true">|</span>
                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('post.min_read', ['minutes' => $readingTime]) }}
                        </span>
                    @endif
                    <span class="text-xs text-[#1a1a1a]/30 font-mono">
                        <span class="text-[#1a1a1a]/15" aria-hidden="true">|</span>
                        <time datetime="{{ $content->created_at->toIso8601String() }}">
                            {{ $content->created_at->format('M j, Y') }}
                        </time>
                    </span>
                @if($locale !== 'en')
                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-[#1a1a1a]/5 text-[#1a1a1a]/50 font-mono">
                        {{ strtoupper($locale) }}
                    </span>
                @endif
                </div>

                {{-- H1 Title --}}
                <h1 class="font-display text-3xl sm:text-4xl md:text-5xl font-bold text-[#1a1a1a] mb-8 leading-[1.15] speakable-intro">
                    {{ $content->title }}
                </h1>

                {{-- Hero Image --}}
                @if($content->image_url)
                <div class="mb-10 overflow-hidden shadow-[0_4px_24px_-8px_rgba(26,26,26,0.15)]" style="border-radius: 2px 24px 2px 24px;">
                    <img
                        src="{{ $content->image_url }}"
                        alt="{{ $displayName }}"
                        class="w-full h-auto"
                        loading="eager"
                    />
                </div>
                @endif

                {{-- Excerpt --}}
                @if($excerpt)
                <div class="mb-10 relative">
                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#bc4749] via-[#bc4749]/50 to-transparent"></div>
                    <p class="pl-6 text-lg sm:text-xl text-[#1a1a1a]/70 leading-relaxed font-serif italic">
                        {{ $excerpt }}
                    </p>
                </div>
                @endif

                {{-- Divider --}}
                <div class="flex items-center gap-4 mb-10">
                    <div class="h-px flex-1 bg-gradient-to-r from-[#1a1a1a]/15 to-transparent"></div>
                    <svg class="size-5 text-[#1a1a1a]/10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <div class="h-px flex-1 bg-gradient-to-l from-[#1a1a1a]/15 to-transparent"></div>
                </div>

                {{-- Article Body --}}
                @if($bodyContent)
                <div class="prose prose-slate max-w-none mb-12 font-serif"
                     style="--tw-prose-body: #1a1a1a; --tw-prose-headings: #1a1a1a; --tw-prose-links: #1b4332; --tw-prose-bold: #1a1a1a; --tw-prose-counters: #bc4749; --tw-prose-bullets: #bc4749; --tw-prose-quotes: #1a1a1a; --tw-prose-quote-borders: #bc4749; --tw-prose-captions: #1a1a1a/60; --tw-prose-code: #1a1a1a; --tw-prose-pre-code: #f7f3ed; --tw-prose-pre-bg: #1a1a1a; --tw-prose-th-borders: #1a1a1a/10; --tw-prose-td-borders: #1a1a1a/10;">
                    {!! Str::markdown($bodyContent) !!}
                </div>
                @endif

                {{-- Language Switcher (bottom of article) --}}
                @if($translations->isNotEmpty())
                <div class="mt-12 mb-10 p-6 sm:p-8 bg-white shadow-[0_4px_20px_-8px_rgba(26,26,26,0.1)] overflow-hidden relative" style="border-radius: 2px 20px 2px 20px;">
                    <div class="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#bc4749]/30 to-transparent"></div>

                    <h3 class="text-sm font-semibold text-[#1a1a1a] mb-5 flex items-center gap-2 font-mono uppercase tracking-wider">
                        <svg class="size-4 shrink-0 text-[#bc4749]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                        </svg>
                        {{ __('post.read_this_article_in') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($translations as $translation)
                            <a href="{{ $translation->locale === 'en' ? route('post.show', $translation->slug) : route('post.locale.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] font-mono tracking-wide {{ $locale === $translation->locale ? 'bg-[#1b4332] text-[#f7f3ed] shadow-sm' : 'bg-[#f7f3ed] text-[#1a1a1a] hover:bg-[#e8e4de]' }}"
                               style="border-radius: 2px 12px 2px 12px;"
                               lang="{{ $translation->locale }}">
                                {{ strtoupper($translation->locale) }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Related Posts / CTA --}}
                <div class="mt-10">
                    <div class="relative overflow-hidden bg-[#1b4332] p-8 sm:p-10 shadow-[0_8px_32px_-8px_rgba(27,67,50,0.4)]" style="border-radius: 2px 24px 2px 24px;">
                        <x-paper-grain class="opacity-50" />

                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="h-px w-8 bg-[#f7f3ed]/40"></span>
                                <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#f7f3ed]/60">
                                    Continue Reading
                                </span>
                            </div>
                            <h3 class="font-display text-2xl sm:text-3xl font-bold text-[#f7f3ed] mb-3">
                                {{ __('post.cta_show_title', ['topic' => $displayName]) }}
                            </h3>
                            <p class="text-[#f7f3ed]/70 text-base leading-relaxed mb-6 font-serif max-w-xl">
                                {{ __('post.cta_show_description') }}
                            </p>
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center gap-2 rounded-full bg-[#f7f3ed] px-6 py-3 text-sm font-semibold text-[#1b4332] transition-all duration-300 hover:bg-white hover:gap-3">
                                {{ __('post.cta_show_button') }}
                                <svg class="size-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <x-footer />
</x-default-layout>
