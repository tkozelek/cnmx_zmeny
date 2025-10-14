<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.partials._head')

<body class="antialiased bg-gray-700 text-gray-200">

@include('layouts.partials._navigation')

<main>
    <div>
        {{ $slot }}
    </div>
</main>

    <x-flash-message />

    <link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>

    <script>
        window.appRoutes = {
            addUserUrl: "{{ route('calendar.toggleUser') }}",
            fileUpload: "{{ route('files.store') }}"
        };
    </script>
</body>
</html>
