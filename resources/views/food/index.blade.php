@section('title', 'Food Database - Glycemic Index & Nutrition for Diabetics | Acara Plate')
@section('meta_description', 'Browse our comprehensive food database with glycemic index information, nutrition facts, and diabetes safety assessments. Make informed food choices for better blood sugar control.')
@section('meta_keywords', 'food database, glycemic index database, diabetes food list, nutrition facts, diabetic food guide, blood sugar friendly foods, low glycemic foods list')

@section('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [{
        "@@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ url('/') }}"
    },{
        "@@type": "ListItem",
        "position": 2,
        "name": "Food Database",
        "item": "{{ url()->current() }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "Food Database - Glycemic Index & Nutrition for Diabetics",
    "description": "Browse our comprehensive food database with glycemic index information, nutrition facts, and diabetes safety assessments.",
    "url": "{{ url()->current() }}",
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    }
}
</script>
@endsection

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url('/') }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Home</span>
        </a>

        <div class="mt-6">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Food Database for Diabetics
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 mb-8 max-w-3xl">
                Explore our comprehensive food database with glycemic impact assessments, nutrition facts from USDA, and diabetes safety insights to help you make informed food choices.
            </p>

            {{-- Food Grid --}}
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10">
                @forelse($foods as $food)
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
                            <h2 class="font-semibold text-slate-900 dark:text-white mb-2 group-hover:text-primary transition-colors">
                                {{ $food->display_name }}
                            </h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                {{ ucfirst($assessment) }} GI
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-16">
                        <svg class="mx-auto size-16 text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No foods found</h3>
                        <p class="text-slate-500 dark:text-slate-400">Check back soon as we're adding more foods to our database.</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($foods->hasPages())
                <div class="mt-8">
                    {{ $foods->links() }}
                </div>
            @endif

            {{-- CTA Section --}}
            <div class="mt-16 bg-linear-to-r from-primary/10 to-primary/5 dark:from-primary/20 dark:to-primary/10 rounded-2xl p-8">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        Want Personalized Blood Sugar Predictions?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        Try our Spike Calculator to see how your specific meals and portions will affect your blood sugar levels.
                    </p>
                    <a
                        href="{{ route('spike-calculator') }}"
                        class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold shadow-lg hover:shadow-xl"
                    >
                        <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Try the Spike Calculator
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
