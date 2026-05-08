@props([
    'items' => [],
])

@if (! empty($items))
    <nav
        aria-label="Breadcrumb"
        {{ $attributes->merge(['class' => 'flex items-center gap-2 px-4 pt-6 font-mono text-[11px] uppercase tracking-[0.14em] text-slate-500 sm:px-6 md:pt-8 lg:px-8 dark:text-slate-400']) }}
    >
        <a href="/" aria-label="Home" class="inline-flex items-center transition-colors hover:text-slate-900 dark:hover:text-white">
            <x-icons.home class="size-3.5" aria-hidden="true" />
        </a>
        @foreach ($items as $crumb)
            <span aria-hidden="true">›</span>
            @if (! empty($crumb['href']))
                <a href="{{ $crumb['href'] }}" class="transition-colors hover:text-slate-900 dark:hover:text-white">
                    {{ $crumb['label'] }}
                </a>
            @else
                <span aria-current="page" class="text-slate-700 dark:text-slate-300">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
