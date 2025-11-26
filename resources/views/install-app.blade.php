<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
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
