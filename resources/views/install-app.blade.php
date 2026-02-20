@section('title', 'Install Acara Plate PWA | Native App Experience on iOS & Android')
@section('meta_description', 'Learn how to install Acara Plate as a Progressive Web App (PWA) on your iPhone or Android device for faster access, offline support, and a more immersive experience.')

@section('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How do I install Acara Plate on my phone?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "On iPhone, open Safari, navigate to the site, tap the Share button, and select 'Add to Home Screen'. On Android, open Chrome, navigate to the site, tap the menu (three dots), and select 'Install app' or 'Add to Home screen'. The app will appear on your home screen like a native app."
            }
        },
        {
            "@@type": "Question",
            "name": "What is a Progressive Web App (PWA)?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A PWA is a web application that can be installed on your device and accessed from your home screen, just like a native app. It offers faster load times, a more immersive full-screen experience, and works with your device's native browser for the best performance."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to download anything from the App Store?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No, Acara Plate is a Progressive Web App that installs directly from your browser. There's no app store download required. Simply visit the website in Safari (iOS) or Chrome (Android) and add it to your home screen for instant access."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Install Acara Plate PWA",
    "description": "Learn how to install Acara Plate as a Progressive Web App on your iPhone or Android device for faster access and a native app experience.",
    "url": "{{ url('/install-app') }}",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    }
}
</script>
@endsection

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center text-slate-600 dark:text-slate-400 hover:underline z-50 relative"
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl speakable-intro">
                <h1>Install Acara Plate</h1>
                <p>
                    For the best experience, use this app with your device's native web browser. Installing Acara Plate as a Progressive Web App (PWA) allows you to access it directly from your home screen, just like a native app, with faster load times and a more immersive experience.
                </p>

                <h2>iOS (Safari)</h2>
                <ol>
                    <li>Open <strong>Safari</strong> on your iPhone or iPad.</li>
                    <li>Navigate to <a href="{{ url('/') }}">{{ url('/') }}</a>.</li>
                    <li>Tap the <strong>Share</strong> button (the square with an arrow pointing up) at the bottom of the screen.</li>
                    <li>Scroll down and tap <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong> in the top right corner.</li>
                </ol>

                <h2>Android (Chrome)</h2>
                <ol>
                    <li>Open <strong>Chrome</strong> on your Android device.</li>
                    <li>Navigate to <a href="{{ url('/') }}">{{ url('/') }}</a>.</li>
                    <li>Tap the <strong>Menu</strong> icon (three dots) in the top right corner.</li>
                    <li>Tap <strong>Install app</strong> or <strong>Add to Home screen</strong>.</li>
                    <li>Follow the on-screen instructions to complete the installation.</li>
                </ol>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
