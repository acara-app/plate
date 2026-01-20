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
        "name": "Tools",
        "item": "{{ $currentUrl }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "Free Diabetes & Nutrition Tools",
    "description": "Science-based tools to help you manage blood sugar, make smarter food choices, and live healthier. No sign-up required.",
    "url": "{{ $currentUrl }}",
    "mainEntity": {
        "@@type": "ItemList",
        "itemListElement": [
            {
                "@@type": "ListItem",
                "position": 1,
                "name": "Glucose Spike Calculator",
                "description": "Check if foods will spike your blood sugar with AI-powered analysis",
                "url": "{{ route('spike-calculator') }}"
            },
            {
                "@@type": "ListItem",
                "position": 2,
                "name": "Food Photo Analyzer",
                "description": "Snap a photo of your meal and get instant macro breakdown",
                "url": "{{ route('snap-to-track') }}"
            },
            {
                "@@type": "ListItem",
                "position": 3,
                "name": "USDA Daily Servings Calculator",
                "description": "Calculate daily food servings based on USDA 2025-2030 Guidelines",
                "url": "{{ route('usda-servings-calculator') }}"
            },
            {
                "@@type": "ListItem",
                "position": 4,
                "name": "Diabetic Food Database",
                "description": "Search foods with glycemic index and diabetic-friendly ratings",
                "url": "{{ route('food.index') }}"
            },
            {
                "@@type": "ListItem",
                "position": 5,
                "name": "Diabetes Log Book",
                "description": "Free printable diabetes log book for tracking",
                "url": "{{ route('diabetes-log-book-info') }}"
            },
            {
                "@@type": "ListItem",
                "position": 6,
                "name": "10-Day Meal Plan",
                "description": "Free 10-day diabetic-friendly meal plan with recipes",
                "url": "{{ route('10-day-meal-plan') }}"
            }
        ]
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ $url }}"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Free Diabetes & Nutrition Tools",
    "description": "Free diabetes and nutrition tools including glucose spike calculator, food photo analyzer, USDA daily servings calculator, and more.",
    "url": "{{ $currentUrl }}",
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ $url }}"
    }
}
</script>
