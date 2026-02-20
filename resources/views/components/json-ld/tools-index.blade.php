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
    "description": "Science-based tools to help you manage blood sugar, make smarter food choices, and live healthier.",
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
                "name": "AI Meal Planner",
                "description": "Get personalized 7-day meal plans tailored to your diabetes type and goals",
                "url": "{{ route('meal-planner') }}"
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
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ $url }}"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "What free tools does Acara Plate offer for diabetes management?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Acara Plate offers free diabetes tools including a glucose spike calculator to predict blood sugar impact, a food photo analyzer for instant nutrition breakdown, a USDA daily servings calculator based on official guidelines, a searchable diabetic food database with glycemic index data, and a printable diabetes log book."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the glucose spike calculator work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The glucose spike calculator uses AI to analyze carbohydrates, fiber, protein, and fat content in any food. It predicts digestion speed and assigns a risk level (Low, Medium, or High) using USDA nutrition data, then suggests healthier alternatives for better blood sugar control."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I track my glucose levels with these tools?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes! You can use the diabetes log book to track blood sugar manually, or connect Telegram for hands-free health logging. For a more comprehensive experience, create a free account to access the digital glucose tracker with automated calculations, trend charts, and A1C estimation."
            }
        },
        {
            "@@type": "Question",
            "name": "Are these tools suitable for Type 2 diabetes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes, all tools are specifically designed for people with Type 2 diabetes and prediabetes. The glucose spike calculator, food database, and meal planner all focus on glycemic impact and blood sugar management. However, these are educational tools and not a substitute for medical advice."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to create an account to use these tools?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Most tools are free to use without an account, including the glucose spike calculator, food database search, and printable log book. Creating a free account unlocks additional features like personalized meal plans, digital tracking with analytics, and AI nutrition coaching."
            }
        }
    ]
}
</script>
