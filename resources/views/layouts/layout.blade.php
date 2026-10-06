<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.partials._head')

<body class="min-h-screen flex flex-col bg-neutral-950 text-neutral-200 antialiased">

@include('layouts.partials._navigation')

<main class="flex-1 flex flex-col">
    <div class="flex-1 flex flex-col">
        {{ $slot }}
    </div>
</main>

    <x-flash-message />


    @livewireScripts
</body>

</html>
