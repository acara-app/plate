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
        "name": "Food Photo Analyzer",
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
            "name": "How does the food photo analyzer work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The tool looks at your photo to find food items. It guesses how much food is there. Then, it calculates the calories, protein, carbs, and fat for you."
            }
        },
        {
            "@@type": "Question",
            "name": "How accurate is the calorie estimation from photos?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Estimates work best when the photo is clear. Lighting matters. If we can see the food clearly, the numbers will be more accurate. The confidence score tells you how sure we are."
            }
        },
        {
            "@@type": "Question",
            "name": "What types of food can the AI recognize?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We recognize most common foods. This includes fruits, vegetables, meats, and grains. Snacks and drinks work too. Make sure the food is easy to see."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Snap to Track - Food Photo Calorie Counter",
    "description": "Analyze food photos to get instant calorie and macro breakdown.",
    "url": "{{ $currentUrl }}",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "Any",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    },
    "author": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ $url }}"
    }
}
</script>
{{-- Speakable Structured Data for Voice Search --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Free Food Photo Analyzer",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
