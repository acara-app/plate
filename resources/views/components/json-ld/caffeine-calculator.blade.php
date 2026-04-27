@props(['url' => url('/'), 'currentUrl' => url()->current()])
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Coffee Caffeine Calculator",
    "description": "Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.",
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
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How much caffeine is safe per day?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "For most healthy adults, up to 400 mg of caffeine per day is generally considered safe. This calculator personalizes that estimate based on your body weight and self-reported caffeine sensitivity, so your safe dose may be lower or higher than the 400 mg average."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the caffeine calculator work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The calculator multiplies your weight in kilograms by a base of 5.7 mg per kg, then scales the result by a sensitivity multiplier (0.7 to 1.3) based on the 5-step sensitivity slider. It divides the resulting safe milligrams by the caffeine content of your chosen drink to estimate how many cups you can have in a day."
            }
        },
        {
            "@@type": "Question",
            "name": "When should I stop drinking coffee before bed?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Caffeine has a half-life of about 5 hours, so it can disrupt sleep many hours after your last cup. The calculator works backward from your bedtime to find the latest time you can drink coffee while keeping residual caffeine below a sleep-friendly threshold."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this caffeine calculator a substitute for medical advice?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. This tool provides educational estimates based on average half-life and sensitivity values. People with heart conditions, anxiety, pregnancy, or who take medications that interact with caffeine should follow their clinician's guidance instead of this calculator."
            }
        },
        {
            "@@type": "Question",
            "name": "Does caffeine affect everyone the same way?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. Genetics, age, body weight, tolerance, pregnancy, and medications all change how quickly your body clears caffeine. The 5-step sensitivity slider lets you adjust the safe dose up or down to better match your personal response."
            }
        }
    ]
}
</script>
