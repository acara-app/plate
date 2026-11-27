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
        "name": "Health Sync",
        "item": "{{ $url }}/tools/health-sync"
    },{
        "@@type": "ListItem",
        "position": 4,
        "name": "Setup Guide",
        "item": "{{ $currentUrl }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "HowTo",
    "name": "Set Up Acara Health Sync",
    "description": "Connect your iPhone health data to Acara Plate in 5 simple steps.",
    "totalTime": "PT5M",
    "step": [
        {
            "@@type": "HowToStep",
            "position": 1,
            "name": "Get the App",
            "text": "Download Acara Health Sync from the App Store ({{ config('acara.health_sync.app_store_url') }}) on your iPhone running iOS {{ config('acara.health_sync.minimum_ios_version') }} or later.",
            "url": "{{ config('acara.health_sync.app_store_url') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 2,
            "name": "Sign In",
            "text": "Open Health Sync on your iPhone and sign in with the same Acara Plate account you use on the web."
        },
        {
            "@@type": "HowToStep",
            "position": 3,
            "name": "Pick Your Data",
            "text": "Open the Sync tab and choose which health data to share. When prompted, allow access to the categories you want to sync in Apple Health."
        },
        {
            "@@type": "HowToStep",
            "position": 4,
            "name": "Start Syncing",
            "text": "After you connect Apple Health, open the Sync tab to check your connection."
        },
        {
            "@@type": "HowToStep",
            "position": 5,
            "name": "Manage Your Connection",
            "text": "Use Settings to manage permissions or view health data. For a manual sync, choose Sync Now. To disconnect your account from the device, choose Sign Out."
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Setup Guide — Acara Health Sync",
    "description": "Step-by-step guide to set up Acara Health Sync. Install the iOS app, sign in, and start syncing your Apple Health data securely.",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "url": "{{ $currentUrl }}"
}
</script>
