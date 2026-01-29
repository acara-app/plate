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
        "name": "Blood Sugar Spike Checker",
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
            "name": "What is a glucose spike and why does it matter for Type 2 diabetes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A glucose spike occurs when blood sugar rises rapidly after eating high-carbohydrate foods. For people with pre-diabetes or Type 2 diabetes, frequent spikes can lead to long-term health complications. This tool helps you predict which foods trigger spikes and find safer alternatives."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the glucose spike checker work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Our AI tool analyzes carbohydrates, fiber, protein, and fat content in any food. It predicts digestion speed and glycemic risk levels (Low, Medium, or High) using USDA nutrition data, then suggests healthier alternatives for better blood sugar control."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I use this tool for pre-diabetes or Type 2 diabetes management?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "This tool is designed for pre-diabetes and Type 2 diabetes meal planning. It provides educational estimates and smart food swaps based on glycemic impact. However, it is not a substitute for professional medical advice or glucose monitoring."
            }
        },
        {
            "@@type": "Question",
            "name": "What foods cause the highest blood sugar spikes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "High-glycemic foods include white rice, white bread, pastries, sugar-sweetened beverages, candy, and fruit juices. These refined carbohydrates digest quickly, causing rapid blood sugar elevation. Whole grains, legumes, non-starchy vegetables, and lean proteins have lower glycemic impact."
            }
        },
        {
            "@@type": "Question",
            "name": "How can I reduce meal glycemic impact naturally?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Pair carbohydrates with protein, healthy fats, or fiber-rich vegetables to slow sugar absorption. Choose whole grains, eat smaller portions, and take a 10-15 minute walk after meals to improve insulin sensitivity."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Blood Sugar Spike Checker",
    "description": "Free AI-powered glucose spike checker. Enter any food to predict its blood sugar impact and get diabetes-friendly food alternatives for Type 2 diabetes and pre-diabetes management.",
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
    "name": "Free AI Blood Sugar Spike Checker",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
