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
        "name": "Acara Health Sync",
        "item": "{{ $currentUrl }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "MobileApplication",
    "name": "Acara Health Sync",
    "description": "A native iOS companion app that securely syncs Apple Health data to your Acara Plate instance using AES-256-GCM end-to-end encryption.",
    "url": "{{ $currentUrl }}",
    "downloadUrl": "{{ config('acara.health_sync.app_store_url') }}",
    "installUrl": "{{ config('acara.health_sync.app_store_url') }}",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "iOS {{ config('acara.health_sync.minimum_ios_version') }}+",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock"
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
            "name": "What is Acara Health Sync?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Acara Health Sync is a free iOS companion app that reads your Apple Health data and securely syncs it to your Acara Plate nutrition dashboard. It eliminates manual data entry by automatically sending glucose, weight, vitals, activity, and nutrition data."
            }
        },
        {
            "@@type": "Question",
            "name": "What health data can be synced?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Health Sync supports 11 categories including glucose, vitals (heart rate, blood pressure, SpO2), body metrics (weight, BMI, body fat), activity (steps, exercise, workouts), mobility (VO2 max), sleep, nutrition (calories, macros, vitamins), reproductive health, hearing, mindfulness, and medications."
            }
        },
        {
            "@@type": "Question",
            "name": "How is my health data secured?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "All health data is encrypted on your iPhone using AES-256-GCM before it leaves the device. Credentials are stored in the iOS Keychain backed by the Secure Enclave. Data goes directly from your phone to your Plate instance with no third-party servers involved."
            }
        },
        {
            "@@type": "Question",
            "name": "Is Acara Health Sync free?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes, Acara Health Sync is completely free. Both the iOS app and the Plate backend are open source. You can self-host everything and audit every line of code."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need an Apple Watch?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No, an Apple Watch is not required. Health Sync reads from Apple Health, which collects data from your iPhone sensors, any connected devices, and manual entries. An Apple Watch simply adds more data sources like heart rate and workout tracking."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Acara Health Sync",
    "description": "Securely sync your Apple Health data to Acara Plate with end-to-end encryption. Track glucose, weight, vitals, activity, and nutrition automatically.",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
