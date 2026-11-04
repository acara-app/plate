@props(['variant' => 'primary', 'href' => null])

@php
    $classes = [
        'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-center text-base font-medium transition-[color,box-shadow,filter] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0',
        $variant === 'outline'
            ? 'border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground'
            : 'relative overflow-hidden border border-white/20 bg-primary text-primary-foreground shadow-[0_0_20px_rgba(16,185,129,0.3),inset_0_1px_0_rgba(255,255,255,0.4),inset_0_-1px_2px_rgba(0,0,0,0.2)] before:absolute before:inset-0 before:bg-linear-to-br before:from-white/30 before:via-transparent before:to-transparent after:absolute after:inset-0 after:bg-linear-to-tl after:from-black/10 after:via-transparent after:to-white/10 hover:shadow-[0_0_30px_rgba(16,185,129,0.5),inset_0_1px_0_rgba(255,255,255,0.5),inset_0_-1px_2px_rgba(0,0,0,0.2)] hover:brightness-110 active:brightness-95',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
