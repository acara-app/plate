@props([
    'slug' => null,
    'anchor' => 'View food details',
    'class' => '',
])

@if($slug)
<a 
    href="{{ route('food.show', $slug) }}" 
    class="text-primary hover:text-primary/80 underline decoration-primary/30 hover:decoration-primary/60 transition-colors {{ $class }}"
    title="View glycemic index and nutrition facts"
>{{ $anchor }}</a>
@endif
