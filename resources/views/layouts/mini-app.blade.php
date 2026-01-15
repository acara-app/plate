<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.components.head')
        @if (App::environment(['production', 'testing']))
            <x-turnstile.scripts />
        @endif
        {{ $jsonLd ?? '' }}
    </head>
    <body>
        <livewire:flash-messages.show />

        <div class="flex min-h-screen flex-col">
            <main class="grow">
                <div class="flex min-h-screen flex-col justify-center overflow-hidden">
                    {{ $slot }}
                </div>
            </main>
        </div>
        @livewireScriptConfig
    </body>
</html>
