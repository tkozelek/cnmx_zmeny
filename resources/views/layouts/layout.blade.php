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

    <link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>
</body>

</html>
