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
            "text": "Download Acara Health Sync from the App Store ({{ config('plate.health_sync.app_store_url') }}) on your iPhone running iOS {{ config('plate.health_sync.minimum_ios_version') }} or later.",
            "url": "{{ config('plate.health_sync.app_store_url') }}"
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
            "text": "Choose which health categories to share with Plate. Toggle individual data types on or off, then approve the Apple Health permissions prompt."
        },
        {
            "@@type": "HowToStep",
            "position": 4,
            "name": "Start Syncing",
            "text": "Your dashboard shows the connection status. Data syncs automatically when you open the app, or tap Sync Now for an immediate sync."
        },
        {
            "@@type": "HowToStep",
            "position": 5,
            "name": "Manage Your Connection",
            "text": "Use the app settings to change data permissions, run a manual sync, view sync history, or disconnect the account from the device."
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
