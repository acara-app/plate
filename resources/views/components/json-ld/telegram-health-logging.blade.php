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
        "name": "Quick Health Logging with Telegram",
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
            "name": "How do I log glucose on Telegram?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Simply send a message like 'My glucose is 140' or 'Fasting glucose 95'. The AI automatically detects it's glucose data, shows you the reading, and asks for confirmation. Reply /yes to save it to your health log."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I log insulin via Telegram?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes! Just say 'Took 5 units of insulin' or 'Bolus 3 units'. You can specify insulin type (basal, bolus, or mixed) and the bot will log it with your health data."
            }
        },
        {
            "@@type": "Question",
            "name": "What health data can I track on Telegram?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "You can log glucose (mg/dL or mmol/L), food and carbs (grams), insulin (units), medication (name and dosage), vitals (weight, blood pressure), and exercise (type and duration)."
            }
        },
        {
            "@@type": "Question",
            "name": "Is Telegram secure for health data?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Your Telegram account is securely linked to your Plate account using a unique token. Health data is stored in your private Plate account, not on Telegram's servers."
            }
        },
        {
            "@@type": "Question",
            "name": "Does the bot understand different units?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes! The AI automatically converts units. Say 'glucose 6.5' (mmol/L) or 'glucose 140' (mg/dL)—it knows the difference. Say 'weight 180 lbs' and it saves in kilograms."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I ask nutrition questions too?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Absolutely! Your AI nutritionist is available 24/7. Ask questions like 'What should I eat for breakfast?' or 'Will pizza spike my glucose?'"
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Quick Health Logging with Telegram",
    "description": "Learn how to log glucose, insulin, carbs, and more via Telegram. Step-by-step guide to tracking your health data hands-free using AI-powered natural language.",
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
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Quick Health Logging with Telegram",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
