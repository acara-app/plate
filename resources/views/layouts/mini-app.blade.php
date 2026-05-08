<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.components.head')
        {{ $jsonLd ?? '' }}
    </head>
    <body class="overflow-x-hidden bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-50">
        <livewire:flash-messages.show />

        <div class="flex min-h-[100dvh] flex-col">
            <main class="grow">
                {{ $slot }}
            </main>
        </div>
        @livewireScriptConfig
    </body>
</html>
