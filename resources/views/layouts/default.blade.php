<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Personalized Nutrition AI Agent | Custom Meal Plans - Plate</title>
        <meta name="description" content="Achieve your health goals with our Personalized Nutrition AI Agent. Get a custom meal plan tailored to your diet, lifestyle, and objectives. Create your effective and sustainable plan today at Plate.">
        <meta name="keywords" content="personalized nutrition, AI meal planner, custom meal plan, diet planning, nutrition assistant, health goals, sustainable meal planning">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="Personalized Nutrition AI Agent | Custom Meal Plans - Plate">
        <meta property="og:description" content="Achieve your health goals with our Personalized Nutrition AI Agent. Get a custom meal plan tailored to your diet, lifestyle, and objectives.">
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Personalized Nutrition AI Agent | Custom Meal Plans - Plate">
        <meta name="twitter:description" content="Achieve your health goals with our Personalized Nutrition AI Agent. Get a custom meal plan tailored to your diet, lifestyle, and objectives.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>

    <body>
       {{ $slot }}
    </body>
</html>
