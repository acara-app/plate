@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', __('post.meta_keywords'))
@section('og_image_alt', __('post.og_image_alt'))
@section('canonical_url', $canonicalUrl)

@section('head')
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ $pageTitle }}",
    "description": "{{ $pageDescription }}",
    "url": "{{ $canonicalUrl }}",
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    }
}
</script>
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": "{{ $pageTitle }}",
    "numberOfItems": {{ $posts->total() }},
    "itemListElement": [
        @foreach($posts as $post)
        {
            "@@type": "ListItem",
            "position": {{ $loop->iteration + (($posts->currentPage() - 1) * $posts->perPage()) }},
            "url": "{{ $post->locale === 'en' ? route('post.show', $post->slug) : route('post.locale.show', ['locale' => $post->locale, 'slug' => $post->slug]) }}",
            "name": "{{ $post->display_name }}"
        }@unless ($loop->last),@endunless
        @endforeach
    ]
}
</script>

    {{-- Pagination SEO links --}}
    @if($posts->currentPage() > 1)
    <link rel="prev" href="{{ $posts->previousPageUrl() }}" />
    @endif
    @if($posts->hasMorePages())
    <link rel="next" href="{{ $posts->nextPageUrl() }}" />
    @endif

    {{-- hreflang alternate links for multilingual SEO --}}
    @foreach($hreflangLinks as $link)
        <link rel="alternate" hreflang="{{ $link['locale'] }}" href="{{ $link['url'] }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $hreflangLinks[0]['url'] }}" />
@endsection

<x-default-layout>
    @include('post._header')

    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

        {{-- Hero Section --}}
        @if($posts->isNotEmpty())
            @php
                $firstPost = $posts->first();
                $firstExcerpt = $firstPost->body['excerpt'] ?? '';
                $firstReadingTime = $firstPost->body['reading_time'] ?? null;
                $firstCategoryLabel = $firstPost->category?->label() ?? '';
                $firstPostUrl = $firstPost->locale === 'en' ? route('post.show', $firstPost->slug) : route('post.locale.show', ['locale' => $firstPost->locale, 'slug' => $firstPost->slug]);
            @endphp

            <a href="{{ $firstPostUrl }}" class="group block mt-10 sm:mt-14 mb-10 sm:mb-14">
                <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-slate-900 dark:bg-slate-800">
                    @if($firstPost->image_url)
                        <div class="aspect-[16/9] sm:aspect-[21/9] overflow-hidden">
                            <img
                                src="{{ $firstPost->image_url }}"
                                alt="{{ $firstPost->display_name }}"
                                class="w-full h-full object-cover opacity-60 group-hover:opacity-70 group-hover:scale-105 transition-all duration-700 ease-out"
                                loading="eager"
                            />
                        </div>
                    @else
                        <div class="aspect-[16/9] sm:aspect-[21/9] bg-gradient-to-br from-primary/30 via-slate-800 to-slate-900 dark:from-primary/20 dark:via-slate-800 dark:to-slate-900"></div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/95 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                        <div class="max-w-3xl">
                            @if($firstCategoryLabel)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wider bg-primary/90 text-white mb-3">
                                    {{ $firstCategoryLabel }}
                                </span>
                            @endif

                            <h1 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-3 leading-tight group-hover:text-primary/90 transition-colors duration-300">
                                {{ $firstPost->display_name }}
                            </h1>

                            @if($firstExcerpt)
                                <p class="text-slate-300 text-sm sm:text-base leading-relaxed line-clamp-2 max-w-2xl">
                                    {{ $firstExcerpt }}
                                </p>
                            @endif

                            <div class="flex items-center gap-2 text-xs text-slate-400 mt-4">
                                @if($firstReadingTime)
                                    <span class="flex items-center gap-1">
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ __('post.min_read', ['minutes' => $firstReadingTime]) }}
                                    </span>
                                    <span aria-hidden="true">&middot;</span>
                                @endif
                                <time datetime="{{ $firstPost->created_at->toIso8601String() }}">
                                    {{ $firstPost->created_at->format('M j, Y') }}
                                </time>
                            </div>

                            <div class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-primary group-hover:gap-3 transition-all duration-300">
                                {{ __('post.read_article') }}
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @else
            <div class="mt-14 mb-10">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                    {{ $pageTitle }}
                </h1>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                    {{ $pageDescription }}
                </p>
            </div>
        @endif

        {{-- Post Grid --}}
        @if($posts->isNotEmpty())
            @php $remainingPosts = $posts->skip(1); @endphp

            @if($remainingPosts->isNotEmpty())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    @foreach($remainingPosts as $post)
                        @include('post._card', ['post' => $post])
                    @endforeach
                </div>
            @endif
        @else
            <div class="text-center py-20">
                <div class="mx-auto size-20 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-6">
                    <svg class="size-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ __('post.no_articles_title') }}</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    {{ __('post.no_articles_description') }}
                </p>
            </div>
        @endif

        {{-- Pagination --}}
        @if ($posts->hasPages())
            <div class="mt-8 mb-10">
                {{ $posts->links() }}
            </div>
        @endif

        {{-- CTA Section --}}
        <div class="mt-10 mb-16">
            <x-cta-block
                title="{{ __('post.cta_index_title') }}"
                description="{{ __('post.cta_index_description') }}"
                button-text="{{ __('post.cta_index_button') }}"
                button-url="{{ route('meet-altani') }}"
            />
        </div>
    </div>

    <x-footer />
</x-default-layout>