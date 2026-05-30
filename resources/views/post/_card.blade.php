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
    class="group relative flex flex-col overflow-hidden bg-white transition-all duration-500 hover:shadow-[0_12px_40px_-12px_rgba(26,26,26,0.18)]"
    style="border-radius: 2px 28px 2px 28px;"
>
    @if($post->image_url)
        <div class="aspect-[4/3] overflow-hidden bg-[#e8e4de]">
            <img
                src="{{ $post->image_url }}"
                alt="{{ $post->display_name }}"
                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.05]"
                loading="lazy"
            />
        </div>
    @else
        <div class="aspect-[4/3] flex items-center justify-center relative overflow-hidden bg-gradient-to-br from-[#f0ebe3] via-[#e8e4de] to-[#f0ebe3]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_40%,rgba(27,67,50,0.04),transparent_60%)]" aria-hidden="true"></div>
            <svg class="size-12 text-[#1a1a1a]/15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
    @endif

    <div class="relative flex flex-col flex-1 p-6 sm:p-7">
        {{-- Top accent line --}}
        <div class="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>

        @if($categoryLabel)
            <span class="inline-flex self-start items-center gap-1.5 font-mono text-[0.65rem] font-semibold uppercase tracking-[0.15em] text-[#bc4749] mb-4">
                <span class="h-1 w-1 rounded-full bg-[#bc4749]"></span>
                {{ $categoryLabel }}
            </span>
        @endif

        <h3 class="font-display text-xl font-bold text-[#1a1a1a] mb-3 leading-snug">
            <x-editorial-underline>{{ $post->display_name }}</x-editorial-underline>
        </h3>

        @if($excerpt)
            <p class="text-sm text-[#1a1a1a]/55 mb-5 line-clamp-2 flex-1 leading-relaxed font-serif">
                {{ $excerpt }}
            </p>
        @endif

        <div class="flex items-center gap-3 text-xs text-[#1a1a1a]/40 mt-auto pt-5 border-t border-dashed border-[#1a1a1a]/10 font-mono">
            @if($readingTime)
                <span class="flex items-center gap-1.5">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('post.min_read', ['minutes' => $readingTime]) }}
                </span>
                <span class="text-[#1a1a1a]/15" aria-hidden="true">|</span>
            @endif
            <time datetime="{{ $post->created_at->toIso8601String() }}">
                {{ $post->created_at->format('M j, Y') }}
            </time>
        </div>
    </div>
</a>
