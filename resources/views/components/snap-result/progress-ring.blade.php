@props(['value', 'size' => 52, 'stroke' => 5, 'indicator' => 'stroke-primary'])

@php
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $progress = min(max((float) $value, 0), 1);
@endphp

<span aria-hidden="true" {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center']) }}>
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" class="stroke-muted" />
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $circumference * (1 - $progress) }}"
            @class([$indicator, 'opacity-0' => $progress === 0.0])
        />
    </svg>
    <span class="absolute inset-0 flex items-center justify-center">{{ $slot }}</span>
</span>
