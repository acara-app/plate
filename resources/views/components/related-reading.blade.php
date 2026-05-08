@props([
    'eyebrow' => 'From the Acara Blog',
    'title' => 'Keep Reading',
    'description' => 'Guides and product updates that explain how Acara turns health data into practical next steps.',
    'articles' => [],
])

@php
    $gridWidth = count($articles) === 1 ? 'max-w-3xl' : 'max-w-6xl';
    $gridColumns = count($articles) === 1 ? '' : 'md:grid-cols-2 lg:grid-cols-3';
@endphp

@if(count($articles) > 0)
    <section {{ $attributes->merge(['class' => 'bg-[#FFFBF5] py-16 sm:py-24']) }}>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#FF6B4A]">
                    {{ $eyebrow }}
                </p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ $title }}
                </h2>
                <p class="mt-4 text-base leading-7 text-slate-600 sm:text-lg">
                    {{ $description }}
                </p>
            </div>

            <div class="{{ $gridWidth }} {{ $gridColumns }} mx-auto mt-10 grid gap-6">
                @foreach($articles as $article)
                    <a
                        href="{{ $article['url'] }}"
                        class="group flex h-full flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:ring-[#FF6B4A]/30"
                    >
                        @isset($article['image'])
                            <div class="aspect-video overflow-hidden bg-slate-100">
                                <img
                                    src="{{ $article['image'] }}"
                                    alt="{{ $article['imageAlt'] ?? $article['title'] }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                    loading="lazy"
                                >
                            </div>
                        @endisset

                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-[#FF6B4A]">
                                <span>{{ $article['category'] ?? 'Product Update' }}</span>
                                @isset($article['readingTime'])
                                    <span class="text-slate-300" aria-hidden="true">/</span>
                                    <span>{{ $article['readingTime'] }}</span>
                                @endisset
                            </div>

                            <h3 class="mt-3 text-xl font-bold leading-tight text-slate-900">
                                {{ $article['title'] }}
                            </h3>
                            <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">
                                {{ $article['description'] }}
                            </p>

                            <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#FF6B4A]">
                                Read the article
                                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
