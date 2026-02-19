@section('title', 'Meet Altani | Your AI Health Coach')
@section('meta_description', 'Altani is your personal AI health coach for diabetes management, nutrition planning, and wellness. Get personalized help, meal plans, and blood sugar insights tailored to your goals.')
@section('meta_keywords', 'AI health coach, diabetes management, nutrition planning, AI nutritionist, meal planning, glucose tracking, wellness coach')
@section('canonical_url', url()->current())
@section('og_image', asset('screenshots/og-meet-altani.webp'))
@section('og_image_width', '1920')
@section('og_image_height', '1096')
@section('og_image_alt', 'Meet Altani - Your personal AI health coach')

@section('head')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Altani - AI Health Coach",
    "description": "Your personal AI health coach for diabetes management, nutrition planning, and wellness. Get personalized help, meal plans, and blood sugar insights.",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "All",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
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
            "name": "What can Altani help me with?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Altani can help with personalized meal planning, blood sugar predictions, nutrition guidance, and wellness support. This includes sleep, stress, exercise, and lifestyle habits."
            }
        },
        {
            "@@type": "Question",
            "name": "How does Altani predict my glucose response?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Altani analyzes the nutritional content of your meals. She looks at carbohydrates, fiber, protein, and fat to model how your body might respond. She learns from your personal data over time to give you more tailored insights."
            }
        },
        {
            "@@type": "Question",
            "name": "Is Altani a replacement for medical care?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. Altani is a wellness tool for education and support. She is not a medical device. She doesn't diagnose conditions or prescribe treatments. Always work with your healthcare provider for diabetes management."
            }
        },
        {
            "@@type": "Question",
            "name": "How do I chat with Altani?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "You can chat with Altani on the web or via Telegram. Just describe what you ate or ask questions about your health. She understands natural language and logs your meals automatically."
            }
        },
        {
            "@@type": "Question",
            "name": "Is my health data secure?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We take privacy seriously. Your health data is never sold to third parties. Altani is part of Acara Plate, an open-source project that is committed to transparent, privacy-first health technology."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "{{ url('/') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "Meet Altani"
        }
    ]
}
</script>
@endsection

<x-default-layout>
    <div class="bg-[#FFFBF5]"> <!-- Cream background color from design -->
        <header class="w-full pt-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                <span>Acara Plate</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                <a href="{{ route('register') }}" class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-all">
                    Get Started
                </a>
            </div>
        </header>
        
        <!-- Hero Section -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-32 overflow-hidden">
            <!-- Decorative Pears -->
            <img src="https://pub-plate-assets.acara.app/images/pear-1.svg" alt="" class="absolute top-0 left-0 w-32 sm:w-48 md:w-64 -translate-y-4 translate-x-4 opacity-90 select-none pointer-events-none">
            <img src="https://pub-plate-assets.acara.app/images/pear-2.svg" alt="" class="absolute top-0 right-0 w-32 sm:w-48 md:w-64 translate-y-8 -translate-x-4 opacity-90 select-none pointer-events-none">

            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <div class="relative z-10 mx-auto max-w-3xl">
                    <div class="mb-6 flex justify-center">
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">
                            Now Available 24/7
                        </span>
                    </div>
                    <h1 class="font-display text-5xl font-bold tracking-tight text-slate-900 sm:text-6xl mb-6">
                        Meet Altani, your ideal<br>
                        <span class="text-emerald-700">AI health coach</span> today.
                    </h1>
                        <p class="mt-6 text-lg leading-8 text-slate-600 max-w-2xl mx-auto">
                            Your personal guide for diabetes, nutrition, and daily wellness. Smart, warm, and always here to help.
                        </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ route('register') }}" class="rounded-full bg-[#FF6B4A] px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-[#E85A3A] transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FF6B4A]">
                            Start Chatting
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Intro/Philosophy Section (Peach/Beige) -->
        <section class="bg-[#FFEFE5] py-20 sm:py-28"> <!-- Muted Peach background -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    <div class="lg:col-span-5 mb-10 lg:mb-0">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-6">
                            "Managing your health shouldn't feel like a lonely battle. It should feel <span class="text-[#FF6B4A] italic">empowering</span>, supported, and clear."
                        </h2>
                        <div class="flex items-center gap-4 mt-8">
                            <img src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp" alt="Altani" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                            <div>
                                <p class="font-bold text-slate-900">Altani</p>
                                <p class="text-sm text-slate-600">Your AI Health Coach</p>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-7 relative">
                        <!-- Image Container with Shape -->
                        <div class="relative rounded-3xl overflow-hidden shadow-xl">
                            <img 
                                src="https://pub-plate-assets.acara.app/images/altani_with_eyes_closed_peaceful_smile_hands_in_relaxed-1024.webp" 
                                alt="Altani smiling peacefully"
                                class="w-full h-full object-cover"
                            >
                            <!-- Decorative shape overlay/background could go here -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

<!-- Features Section (Dark Green) -->
        <section class="bg-[#0E3F3B] py-20 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16">
                    <div class="lg:col-span-5 text-white mb-12 lg:mb-0">
                        <h2 class="font-display text-4xl font-bold tracking-tight sm:text-5xl mb-6 text-[#FF6B4A]">
                            Your Health<br>Responsibility
                        </h2>
                        <p class="text-lg leading-relaxed text-emerald-100/90 mb-8">
                            Altani uses medical knowledge and AI to give you helpful guidance. She learns from your data to predict blood sugar trends and suggest meals that work for your body.
                        </p>
                        <div class="inline-flex items-center gap-2 text-white font-medium border-b border-[#FF6B4A] pb-1">
                            <span>Explore features</span>
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                    </div>

                    <div class="lg:col-span-7 grid md:grid-cols-2 gap-6">
                        <!-- Card 1 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg transform md:translate-y-8">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">01</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">Evidence-Based</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Every recommendation is based on nutritional science and approved guidelines for diabetes care.
                            </p>
                        </div>
                        
                        <!-- Card 2 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">02</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">Real-Time Prediction</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Altani predicts how your blood sugar will respond to a meal before you eat. This helps you make smarter choices.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg transform md:translate-y-8">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">03</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">Natural Chat</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Just talk naturally. Say "I had a salad with chicken." Altani understands, logs the nutrition, and updates your insights right away.
                            </p>
                        </div>

                        <!-- Card 4 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">04</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">Privacy First</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                We are open source and privacy-focused. Your health data belongs to you. It is not sold to advertisers or other companies.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Philosophy Section (Sticky Notes) -->
        <section class="bg-[#FFFBF5] py-20 sm:py-32 overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-[#FF6B4A] font-bold tracking-wider uppercase text-sm">Philosophy</span>
                <h2 class="font-display text-4xl font-bold text-slate-900 sm:text-5xl mt-3 mb-16">
                    Warm community guidance
                </h2>

                <div class="relative max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                     <!-- Sticky Note 1 (Orange) -->
                    <div class="md:-rotate-3 hover:rotate-0 transition-transform duration-300">
                        <div class="bg-[#FF6B4A] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                             <div>
                                <h3 class="text-white font-bold text-xl mb-4">01<br>Expertise</h3>
                                <p class="text-white/90 leading-relaxed font-medium">
                                    "Guidance that adapts to your metabolic health, not generic advice."
                                </p>
                             </div>
                             <div class="mt-4 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                             </div>
                        </div>
                    </div>

                    <!-- Sticky Note 2 (Pink) -->
                    <div class="md:rotate-2 hover:rotate-0 transition-transform duration-300 md:-translate-y-8">
                        <div class="bg-[#F8D4D8] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                            <div>
                                <h3 class="text-slate-900 font-bold text-xl mb-4">02<br>Empathy</h3>
                                <p class="text-slate-800/90 leading-relaxed font-medium">
                                    "No judgment. Just support. We celebrate every step forward, no matter how small."
                                </p>
                            </div>
                            <div class="mt-4 w-8 h-8 rounded-full bg-slate-900/10 flex items-center justify-center text-slate-900">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Note 3 (Blue/Purple) -->
                    <div class="md:-rotate-2 hover:rotate-0 transition-transform duration-300">
                        <div class="bg-[#6B71F3] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                            <div>
                                <h3 class="text-white font-bold text-xl mb-4">03<br>Available</h3>
                                <p class="text-white/90 leading-relaxed font-medium">
                                    "24/7 support right in your pocket. Ask questions, log meals, get answers instantly."
                                </p>
                            </div>
                            <div class="mt-4 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                    </div>

                     <!-- Sticky Note 4 (Dark Green - Optional/Extra for layout balance if needed, or keeping it 3 cols is fine. Design usually has a cluster. I'll add a 4th one centered below or offset to create that "pile" look) -->
                     
                     <div class="hidden md:block absolute -right-12 top-10 md:rotate-6 w-48 opacity-20 pointer-events-none">
                        <div class="bg-[#0E3F3B] p-4 h-48"></div>
                     </div>
                </div>
            </div>
        </section>

<!-- Expertise Deep Dive Section -->
        <section class="py-20 sm:py-32 bg-white overflow-hidden">
             <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Feature 1 -->
                <div class="lg:grid lg:grid-cols-2 gap-16 items-center mb-24">
                    <div class="mb-10 lg:mb-0">
                        <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-4">
                            Meal plans that actually<br>fit your life.
                        </h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-6">
                            Whether you're Keto, Mediterranean, or Vegan, Altani creates 7-day meal plans for your blood sugar goals and calorie needs. She also generates the grocery lists for you.
                        </p>
                        <ul class="space-y-3 text-slate-600">
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Macros & Calorie Tracking
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Prep Times & Recipes
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Auto-generated Grocery Lists
                            </li>
                        </ul>
                    </div>
                    <div class="relative">
                         <div class="absolute -inset-4 bg-emerald-50 rounded-3xl transform rotate-2 -z-10"></div>
                         <img 
                            src="https://pub-plate-assets.acara.app/images/altani_holding_plate-1024.webp" 
                            alt="Altani holding a healthy meal"
                            class="rounded-2xl shadow-xl w-full"
                        >
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="lg:grid lg:grid-cols-2 gap-16 items-center mb-24">
                    <div class="order-2 lg:order-1 relative">
                         <div class="absolute -inset-4 bg-rose-50 rounded-2xl transform -rotate-2 -z-10"></div>
                         <img 
                            src="https://pub-plate-assets.acara.app/images/altani-pointing_at_floating_holographic_glucose_chart-1024.webp" 
                            alt="Altani analyzing glucose data"
                            class="rounded-xl shadow-xl w-full"
                        >
                    </div>
                    <div class="order-1 lg:order-2 mb-10 lg:mb-0">
                         <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                        </div>
                        <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-4">
                            See the spike<br>before you eat.
                        </h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-6">
                            Unsure about that pasta? Altani predicts your blood sugar response before you take a bite. This helps you make smarter choices without the guesswork.
                        </p>
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                            <p class="text-sm text-slate-500 italic">
                                "Altani, will this bagel spike me?"
                            </p>
                            <p class="text-sm text-slate-900 font-medium mt-2">
                                "For you, a plain bagel might cause a sharp rise. Try pairing it with avocado or eggs to flatten the curve."
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 (New: Clinical Precision) -->
                <div class="lg:grid lg:grid-cols-2 gap-16 items-center mb-24">
                    <div class="mb-10 lg:mb-0">
                         <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center text-cyan-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </div>
                        <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-4">
                            Clinical precision<br>at your fingertips.
                        </h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-6">
                            Altani is trained on approved medical guidelines and nutritional science. Whether you're managing diabetes or trying to improve your performance, her guidance is precise, safe, and based on data.
                        </p>
                        <ul class="space-y-3 text-slate-600">
                             <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Evidence-Based Recommendations
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Secure Health Data Processing
                            </li>
                        </ul>
                    </div>
                    <div class="relative">
                         <div class="absolute -inset-4 bg-cyan-50 rounded-2xl transform rotate-2 -z-10"></div>
                         <img 
                            src="https://pub-plate-assets.acara.app/images/altani_wearing_high-tech_futuristic_teal_scrubs-1024.webp" 
                            alt="Altani in high-tech medical attire"
                            class="rounded-xl shadow-xl w-full"
                        >
                    </div>
                </div>

                <!-- Feature 4 (New: Thoughtful Analysis) -->
                <div class="lg:grid lg:grid-cols-2 gap-16 items-center">
                    <div class="order-2 lg:order-1 relative">
                         <div class="absolute -inset-4 bg-amber-50 rounded-2xl transform -rotate-2 -z-10"></div>
                         <img 
                            src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp" 
                            alt="Altani considering options"
                            class="rounded-xl shadow-xl w-full"
                        >
                    </div>
                    <div class="order-1 lg:order-2 mb-10 lg:mb-0">
                         <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                        </div>
                        <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl mb-4">
                            Thoughtful analysis,<br>not just answers.
                        </h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-6">
                            Health isn't simple. Altani looks at everything: your sleep, stress, recent meals, and long-term goals. She gives you a complete picture of your wellness.
                        </p>
                        <p class="text-slate-600 leading-relaxed">
                            She doesn't just react. She thinks ahead. She spots patterns you might miss and suggests small changes that lead to big results over time.
                        </p>
                    </div>
                </div>
             </div>
        </section>

        <!-- Final CTA Section -->
        <section class="bg-[#FFEFE5] py-20 sm:py-32">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                <div class="mb-10 relative inline-block">
                     <div class="absolute inset-0 bg-white/50 blur-2xl rounded-full"></div>
                     <img 
                        src="https://pub-plate-assets.acara.app/images/altani-waving-hello-1024.webp" 
                        alt="Start a conversation with Altani"
                        class="relative mx-auto w-48 h-48 object-cover rounded-full shadow-xl border-4 border-white"
                    >
                </div>
                
                <h2 class="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl mb-6">
                    Start a conversation<br>with Altani.
                </h2>
                <p class="text-xl text-slate-600 mb-10 max-w-2xl mx-auto">
                    She's ready to help you on your health journey. You can use Telegram without installing any apps. Or chat right here on the web.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-[#FF6B4A] px-8 py-4 text-lg font-semibold text-white shadow-lg hover:bg-[#E85A3A] transition-all hover:-translate-y-1">
                        Chat Now
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </a>
                    <a href="https://t.me/AcaraPlate_bot" target="_blank" class="inline-flex items-center justify-center rounded-full bg-white px-8 py-4 text-lg font-semibold text-[#0088cc] shadow-sm hover:bg-slate-50 border border-slate-200 transition-all hover:-translate-y-1">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.06-.14-.04-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                        Find on Telegram
                    </a>
                </div>
                
                <p class="mt-12 text-sm font-medium text-slate-500">
                    OPEN SOURCE • PRIVACY FOCUSED • COMMUNITY DRIVEN
                </p>
            </div>
        </section>

    </div>
    <x-footer />
</x-default-layout>
