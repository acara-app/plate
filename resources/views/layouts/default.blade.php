<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts.components.head')
    </head>

    <body>
       {{ $slot }}
       
       @livewireScripts
    </body>
</html>
