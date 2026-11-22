@props(['variant' => 'primary', 'size' => 'base', 'href' => null])

@php
    $classes = [
        'inline-flex min-h-13 w-full touch-manipulation items-center justify-center gap-2 rounded-full px-5 text-center font-semibold transition duration-150 ease-out outline-none select-none focus-visible:ring-[3px] focus-visible:ring-ring/50 active:scale-[0.97] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none motion-reduce:active:scale-100 [&_svg]:size-4 [&_svg]:shrink-0',
        $size === 'sm' ? 'text-[15px]' : 'text-base',
        match ($variant) {
            'plain' => 'bg-card text-foreground hover:bg-card/70',
            'plan' => 'bg-foreground text-balance text-background hover:bg-foreground/85',
            default => 'relative overflow-hidden border border-white/20 bg-primary text-primary-foreground shadow-[0_0_20px_rgba(16,185,129,0.3),inset_0_1px_0_rgba(255,255,255,0.4),inset_0_-1px_2px_rgba(0,0,0,0.2)] before:absolute before:inset-0 before:bg-linear-to-br before:from-white/30 before:via-transparent before:to-transparent after:absolute after:inset-0 after:bg-linear-to-tl after:from-black/10 after:via-transparent after:to-white/10 hover:brightness-110',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
