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
        "name": "Spike Calculator",
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
            "name": "What is a glucose spike and why should I care?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A spike happens when your blood sugar goes up fast after you eat. You might feel tired or hungry afterwards. Keeping your levels steady helps you feel better and stay healthy."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the glucose spike checker work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We look at the carbs, fiber, protein, and fat in the food. Then we give you a risk level: Low, Medium, or High. We also suggest foods that might be better for your blood sugar."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this tool a replacement for medical advice?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. This tool gives you estimates. It does not replace your doctor. Talk to a medical professional if you have health questions."
            }
        },
        {
            "@@type": "Question",
            "name": "What foods typically cause high blood sugar spikes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "White bread, white rice, and sugary drinks often cause spikes. Candy and pastries do too. These foods have lots of carbs but not much fiber. Your body digests them fast, which sends sugar into your blood quickly."
            }
        },
        {
            "@@type": "Question",
            "name": "How can I reduce the glycemic impact of my meals?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Add protein or healthy fats to your meal. Choose whole grains instead of white ones. Eat vegetables first. A short walk after eating also helps."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Glucose Spike Checker",
    "description": "Check if foods will raise your blood sugar. Get simple risk levels and better food ideas.",
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
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "150"
    }
}
</script>
{{-- Speakable Structured Data for Voice Search --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Free Glucose Spike Checker",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
