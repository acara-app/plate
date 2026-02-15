<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'Acara Plate - AI Diabetes Meal Planner & Glucose Tracker')</title>
        <meta name="description" content="@yield('meta_description', 'Acara Plate is an AI-powered nutrition platform for diabetes management. Get personalized meal plans, track glucose levels, and achieve your health goals.')">
        <meta name="keywords" content="@yield('meta_keywords', 'diabetes nutrition, AI meal planner, glucose tracking, personalized meal plans, diabetes management, blood sugar tracking, diabetic meal planning, AI nutrition assistant')">
        <link rel="canonical" href="@yield('canonical_url', url()->current())">
        <meta name="robots" content="index, follow">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="@yield('title', 'Acara Plate - AI Nutrition for Diabetes')">
        <meta property="og:description" content="@yield('meta_description', 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels to achieve your health goals.')">
        <meta property="og:image" content="@yield('og_image', asset('banner-acara-plate.webp'))">
        <meta property="og:image:width" content="@yield('og_image_width', '1200')">
        <meta property="og:image:height" content="@yield('og_image_height', '630')">
        <meta property="og:image:alt" content="@yield('og_image_alt', 'Acara Plate - AI Nutrition for Diabetes Management')">
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="@yield('title', 'Acara Plate - AI Nutrition for Diabetes')">
        <meta name="twitter:description" content="@yield('meta_description', 'AI-powered nutrition platform for diabetes management. Get personalized meal plans and track glucose levels to achieve your health goals.')">
        <meta name="twitter:image" content="@yield('og_image', asset('banner-acara-plate.webp'))">
        <meta name="twitter:image:alt" content="@yield('og_image_alt', 'Acara Plate - AI Nutrition for Diabetes Management')">

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}">
        <link rel="manifest" href="/build//manifest.webmanifest">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @production
            <script defer src="https://cloud.umami.is/script.js" data-website-id="00659ffa-f13b-411a-81a7-76d2bd81d2c6"></script>
        @endproduction

        @vite(['resources/css/app.css'])

        @yield('head')
    </head>

    <body>
       {{ $slot }}
    </body>
</html>
