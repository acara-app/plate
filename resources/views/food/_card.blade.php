@php
    $assessment = $food->body['glycemic_assessment'] ?? 'medium';
    $badgeColors = [
        'low' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        'high' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    ];
    $badgeColor = $badgeColors[$assessment] ?? $badgeColors['medium'];
@endphp

<a 
    href="{{ route('food.show', $food->slug) }}"
    class="group bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-200 overflow-hidden border border-slate-200 dark:border-slate-700 hover:border-primary dark:hover:border-primary"
>
    @if($food->image_url)
        <div class="aspect-square overflow-hidden bg-slate-100 dark:bg-slate-700">
            <img 
                src="{{ $food->image_url }}" 
                alt="{{ $food->display_name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                loading="lazy"
            />
        </div>
    @else
        <div class="aspect-square bg-linear-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center">
            <svg class="size-16 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    @endif
    <div class="p-4">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-2 group-hover:text-primary transition-colors line-clamp-2">
            {{ $food->display_name }}
        </h3>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                {{ ucfirst($assessment) }} GI
            </span>
            @if($food->category)
                <span class="text-xs text-slate-400 dark:text-slate-500">
                    {{ $food->category->label() }}
                </span>
            @endif
        </div>
    </div>
</a>
