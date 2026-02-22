<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.components.head')
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
