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
    $englishTranslation = $translations->firstWhere('locale', 'en');
    $xDefaultUrl = $locale === 'en' || $englishTranslation === null
        ? $postUrl
        : route('post.show', $englishTranslation->slug);
    $ogLocale = match ($locale) {
        'mn' => 'mn_MN',
        'fr' => 'fr_FR',
        default => 'en_US',
    };
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

    {{-- hreflang alternate links for multilingual SEO --}}
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $postUrl }}" />
    @foreach($translations as $translation)
        <link rel="alternate" hreflang="{{ $translation->locale }}" href="{{ $translation->locale === 'en' ? route('post.show', $translation->slug) : route('post.locale.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}" />

    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $content->title }}",
    "description": "{{ $content->meta_description }}",
    "image": "{{ $content->image_url ?? asset('banner-acara-plate.webp') }}",
    "inLanguage": "{{ $locale }}",
    "author": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}"
        }
    },
    "datePublished": "{{ $content->created_at->toIso8601String() }}",
    "dateModified": "{{ $content->updated_at->toIso8601String() }}",
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ $postUrl }}"
    }
}
</script>

    {{-- Language switcher for search engines --}}
    @if($translations->isNotEmpty())
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "{{ $content->title }}",
    "url": "{{ $postUrl }}",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    }
}
</script>
    @endif
@endsection

<x-default-layout>
    @include('post._header')

    <div class="mx-auto my-16 max-w-3xl px-6 lg:px-8">
        <article class="mt-6">
            {{-- Category & Reading Time --}}
            <div class="flex items-center gap-3 mb-4">
                @if($content->category)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary">
                        {{ $content->category->label() }}
                    </span>
                @endif
                @if($readingTime)
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('post.min_read', ['minutes' => $readingTime]) }}
                    </span>
                @endif
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $content->created_at->format('M j, Y') }}
                </span>
            @if($locale !== 'en')
                <span class="px-2 py-0.5 text-xs font-medium rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    {{ strtoupper($locale) }}
                </span>
            @endif
            </div>

            {{-- H1 Title --}}
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-6 speakable-intro">
                {{ $content->title }}
            </h1>

            {{-- Hero Image --}}
            @if($content->image_url)
            <div class="mb-8 rounded-2xl overflow-hidden shadow-lg">
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
            <div class="mb-8 text-lg text-slate-600 dark:text-slate-300 leading-relaxed border-l-4 border-primary pl-4">
                {{ $excerpt }}
            </div>
            @endif

            {{-- Article Body --}}
            @if($bodyContent)
            <div class="prose prose-slate dark:prose-invert max-w-none mb-10">
                {!! Str::markdown($bodyContent) !!}
            </div>
            @endif

            {{-- Language Switcher (bottom of article) --}}
            @if($translations->isNotEmpty())
            <div class="mt-10 mb-8 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.966 18.966 0 016.416 6m0 0a18.966 18.966 0 01-1.14-1.686M6.416 6L5.07 3.95M19.95 16.5A18.966 18.966 0 0115.936 12m0 0a18.966 18.966 0 01-1.14-1.686M15.936 12l1.346-2.05M15.936 12L18 15m-3-3l1.346 2.05" />
                    </svg>
                    {{ __('post.read_this_article_in') }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    @if($englishTranslation || $locale === 'en')
                    <a href="{{ $locale === 'en' ? $postUrl : route('post.show', $englishTranslation->slug) }}"
                       class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $locale === 'en' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600' }} transition-colors"
                       lang="en">
                        English
                    </a>
                    @endif
                    @foreach($translations as $translation)
                        <a href="{{ $translation->locale === 'en' ? route('post.show', $translation->slug) : route('post.locale.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
                           class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $locale === $translation->locale ? 'bg-primary text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600' }} transition-colors"
                           lang="{{ $translation->locale }}">
                            {{ strtoupper($translation->locale) }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Related Posts / CTA --}}
            <div class="my-10">
                <x-cta-block
                    title="{{ __('post.cta_show_title', ['topic' => $displayName]) }}"
                    description="{{ __('post.cta_show_description') }}"
                    button-text="{{ __('post.cta_show_button') }}"
                    button-url="{{ route('meet-altani') }}"
                />
            </div>
        </article>
    </div>

    <x-footer />
</x-default-layout>