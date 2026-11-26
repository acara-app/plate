@props([
    'size' => 'md',
    'variant' => 'dark',
    'label' => 'Download Acara Health Sync on the App Store',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'px-4 py-2 text-xs',
        'lg' => 'px-7 py-4 text-base',
        default => 'px-5 py-3 text-sm',
    };

    $iconSize = match ($size) {
        'sm' => 'h-5 w-5',
        'lg' => 'h-8 w-8',
        default => 'h-6 w-6',
    };

    $captionSize = match ($size) {
        'sm' => 'text-[9px]',
        'lg' => 'text-xs',
        default => 'text-[10px]',
    };

    $variantClasses = match ($variant) {
        'outline' => 'border border-slate-900/10 bg-white text-slate-900 hover:bg-slate-50 dark:border-white/15 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800',
        default => 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100',
    };
@endphp

<a
    href="{{ config('acara.health_sync.app_store_url') }}"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="{{ $label }}"
    class="group inline-flex items-center gap-3 rounded-xl font-semibold shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $sizeClasses }} {{ $variantClasses }}"
>
    <svg class="{{ $iconSize }} shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" />
    </svg>
    <span class="flex flex-col leading-tight">
        <span class="{{ $captionSize }} font-normal tracking-wide opacity-70">Download on the</span>
        <span class="font-semibold">App Store</span>
    </span>
</a>
