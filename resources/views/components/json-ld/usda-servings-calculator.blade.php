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
        "item": "{{ $url }}/tools"
    },{
        "@@type": "ListItem",
        "position": 3,
        "name": "USDA Daily Servings Calculator",
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
            "name": "What are the USDA 2025-2030 Dietary Guidelines?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The Dietary Guidelines for Americans 2025-2030 provide science-based advice on what to eat and drink to promote health, reduce chronic disease risk, and meet nutrient needs. They're updated every 5 years by the USDA and HHS."
            }
        },
        {
            "@@type": "Question",
            "name": "How do I know how many calories I need?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Calorie needs depend on age, sex, and activity level. Generally: sedentary adults need 1,600-2,000 calories; moderately active need 1,800-2,400; and very active need 2,000-3,200. Consult a healthcare provider for personalized advice."
            }
        },
        {
            "@@type": "Question",
            "name": "What is the Low-Carb Diabetic mode?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The Low-Carb mode adjusts the standard USDA guidelines for people managing blood sugar. It reduces grain servings by 50% and increases protein and vegetable servings by 25%. This is not medical advice - consult your doctor."
            }
        },
        {
            "@@type": "Question",
            "name": "What do the FDA sugar limits mean?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The FDA has set maximum added sugar limits for foods to qualify for the 'Healthy' label. For example, dairy products must have no more than 2.5g added sugar per ⅔ cup. This helps you identify truly healthy options at the grocery store."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "USDA 2025-2030 Daily Serving Calculator",
    "description": "Calculate your daily food servings based on official USDA 2025-2030 Dietary Guidelines. Adjust for calories, view FDA sugar limits, and get diabetic-friendly recommendations.",
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
    "featureList": [
        "Daily serving recommendations by calorie level",
        "Support for 1,000 to 3,200 calorie diets",
        "FDA added sugar limits for healthy eating",
        "Low-carb diabetic mode adjustments",
        "Visual progress tracking for food groups"
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "USDA 2025-2030 Daily Serving Calculator",
    "description": "Official USDA dietary guidelines calculator with interactive calorie slider and FDA sugar limits.",
    "url": "{{ $currentUrl }}",
    "mainEntity": {
        "@@type": "Dataset",
        "name": "USDA Dietary Guidelines 2025-2030 Serving Recommendations",
        "description": "Daily food group serving recommendations based on calorie intake levels from 1,000 to 3,200 calories.",
        "license": "https://www.usa.gov/government-works",
        "creator": {
            "@@type": "Organization",
            "name": "U.S. Department of Agriculture",
            "url": "https://www.usda.gov/"
        }
    }
}
</script>
