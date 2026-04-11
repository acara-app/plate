@props([
    'post',
])

@php
    $excerpt = $post->body['excerpt'] ?? '';
    $readingTime = $post->body['reading_time'] ?? null;
    $categoryLabel = $post->category?->label() ?? '';
@endphp

<a
    href="{{ $post->locale === 'en' ? route('post.show', $post->slug) : route('post.locale.show', ['locale' => $post->locale, 'slug' => $post->slug]) }}"
    class="group flex flex-col bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-800 hover:border-primary/40 dark:hover:border-primary/40 transition-[transform,box-shadow,border-color] duration-250 hover:-translate-y-1 hover:shadow-[0_20px_40px_-12px_oklch(0.45_0.02_260/0.15)] dark:hover:shadow-[0_20px_40px_-12px_oklch(0_0_0/0.4)]"
>
    @if($post->image_url)
        <div class="aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
            <img
                src="{{ $post->image_url }}"
                alt="{{ $post->display_name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                loading="lazy"
            />
        </div>
    @else
        <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 dark:from-slate-800 dark:via-slate-900 dark:to-slate-800 flex items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_40%,oklch(0.65_0.19_165/0.08),transparent_60%)]" aria-hidden="true"></div>
            <svg class="size-14 text-slate-300/70 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
    @endif

    <div class="flex flex-col flex-1 p-5 pb-6">
        @if($categoryLabel)
            <span class="inline-flex self-start items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wider bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary mb-3">
                {{ $categoryLabel }}
            </span>
        @endif

        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-primary transition-colors duration-200 line-clamp-2 leading-snug">
            {{ $post->display_name }}
        </h3>

        @if($excerpt)
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 flex-1 leading-relaxed">
                {{ $excerpt }}
            </p>
        @endif

        <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 mt-auto pt-4 border-t border-slate-100 dark:border-slate-800">
            @if($readingTime)
                <span class="flex items-center gap-1">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('post.min_read', ['minutes' => $readingTime]) }}
                </span>
                <span class="text-slate-300 dark:text-slate-700" aria-hidden="true">&middot;</span>
            @endif
            <time datetime="{{ $post->created_at->toIso8601String() }}">
                {{ $post->created_at->format('M j, Y') }}
            </time>
        </div>
    </div>
</a>