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
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How does the AI food photo analyzer work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Upload a photo of your meal and our AI vision model identifies each food item, estimates portion size, and calculates calories, protein, carbs, and fat for every item plus the full meal. Nutrition values are derived from USDA FoodData Central reference data, and you get a confidence score so you know how reliable each estimate is."
            }
        },
        {
            "@@type": "Question",
            "name": "How accurate are calorie estimates from food photos?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Accuracy depends on photo clarity, lighting, and how visible each ingredient is. In good conditions, AI photo estimates land within roughly 10–20% of actual values for whole foods, and the tool returns a confidence score (0–100%) for each meal so you can judge reliability. Mixed dishes, sauces, and oils are harder to estimate than visible whole foods."
            }
        },
        {
            "@@type": "Question",
            "name": "What types of food can the AI recognize?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The analyzer recognizes most common foods: fruits, vegetables, grains, meats, fish, dairy, packaged snacks, drinks, and prepared dishes from many cuisines. It works best when each item is clearly visible from above with good lighting. Hidden ingredients (oils, sauces, dressings, broths) are harder to detect, so single-ingredient and well-lit plate shots produce the most reliable results."
            }
        },
        {
            "@@type": "Question",
            "name": "Is my food photo kept private?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. Your photo is used only to generate the nutrition analysis. Livewire stores it as a temporary upload while the scan runs, then we delete that temporary file as soon as the result or error is returned. We do not retain images, share them with third parties, or use them to train AI models. Authenticated users can opt to log meals with photos to their personal history; on this public tool, no image is saved."
            }
        },
        {
            "@@type": "Question",
            "name": "How do I use Snap to Track?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Open this page on your phone or laptop, tap the upload area to take a new photo or pick one from your gallery, then tap Analyze Food. In about 5–15 seconds you get a per-item breakdown of calories, protein, carbs, and fat plus meal totals. No signup is required to try it; create a free account to save and track meals over time."
            }
        }
    ]
}
</script>
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
