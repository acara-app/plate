@props(['location'])

<a
    href="{{ route('snap-to-track') }}"
    data-umami-event="snap_to_track_cta_click"
    data-umami-event-location="{{ $location }}"
    {{ $attributes->class('font-semibold underline decoration-primary decoration-2 underline-offset-4 transition-colors hover:text-primary') }}
>{{ $slot }}</a>