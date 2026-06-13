@props(['url' => url('/'), 'currentUrl' => url()->current()])
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [{
        "@@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ $url }}"
    },{
        "@@type": "ListItem",
        "position": 2,
        "name": "Free Tools",
        "item": "{{ route('tools.index') }}"
    },{
        "@@type": "ListItem",
        "position": 3,
        "name": "AI Food Photo Analyzer",
        "item": "{{ $currentUrl }}"
    }]
}
</script>
@php
$faqPage = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect(App\Services\AiTransparency::snapToTrackFaqs())->map(fn (array $faq): array => [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['a'],
        ],
    ])->all(),
];
@endphp
<script type="application/ld+json">{!! json_encode($faqPage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "HowTo",
    "name": "How to count calories and macros from a food photo",
    "description": "A 3-step guide to estimating calories, protein, carbs, and fat for a meal using an AI food photo analyzer.",
    "totalTime": "PT15S",
    "supply": [
        {"@@type": "HowToSupply", "name": "A meal you want to analyze"},
        {"@@type": "HowToSupply", "name": "A phone or computer with a camera"}
    ],
    "tool": [
        {"@@type": "HowToTool", "name": "Acara Plate Snap to Track AI Food Photo Analyzer"}
    ],
    "step": [
        {
            "@@type": "HowToStep",
            "position": 1,
            "name": "Snap a photo of your meal",
            "text": "Take a clear, well-lit photo of your plate from directly above. Make sure all food items are visible and not stacked.",
            "url": "{{ $currentUrl }}#how-it-works"
        },
        {
            "@@type": "HowToStep",
            "position": 2,
            "name": "Let the AI identify each food item",
            "text": "Upload the photo. The AI vision model identifies each ingredient and estimates the portion size for every item it sees.",
            "url": "{{ $currentUrl }}#how-it-works"
        },
        {
            "@@type": "HowToStep",
            "position": 3,
            "name": "Get an instant macro breakdown",
            "text": "In about 5–15 seconds you receive total calories, protein, carbs, and fat for the meal plus a per-item breakdown and a confidence score.",
            "url": "{{ $currentUrl }}#how-it-works"
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Snap to Track - AI Food Photo Analyzer",
    "description": "Free AI food photo analyzer. Upload a photo of any meal to instantly estimate calories, protein, carbs, and fat per ingredient using AI vision analysis and USDA-aligned nutrition references. No signup required.",
    "url": "{{ $currentUrl }}",
    "applicationCategory": "HealthApplication",
    "applicationSubCategory": "NutritionTracking",
    "operatingSystem": "Any",
    "browserRequirements": "Requires JavaScript and a modern browser",
    "datePublished": "2025-08-15",
    "dateModified": "{{ now()->toDateString() }}",
    "inLanguage": "en",
    "isAccessibleForFree": true,
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    },
    "featureList": [
        "AI-powered food recognition from a photo",
        "Per-ingredient calorie and macro breakdown",
        "Total calories, protein, carbs, and fat per meal",
        "Confidence score for each analysis",
        "USDA-aligned nutrition values",
        "No signup required to try"
    ],
    "creator": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ $url }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ $url }}",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('banner-acara-plate.webp') }}"
        }
    }
}
</script>
{{-- Speakable Structured Data for Voice Search --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "AI Food Photo Analyzer",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-definition", ".speakable-how-it-works"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
