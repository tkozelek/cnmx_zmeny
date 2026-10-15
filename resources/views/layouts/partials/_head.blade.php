<head>
    {{-- Lets CSS hide scroll-reveal content only when JS is there to reveal it again - and shows it
         anyway if the welcome script hasn't started within 4 s (blocked, offline, stale build). --}}
    <script>
        document.documentElement.classList.add('js');
        setTimeout(function () {
            var root = document.documentElement;
            if (!root.classList.contains('motion-ready')) root.classList.remove('js');
        }, 4000);
    </script>
    <title>{{ $title ? "{$title} | Cinemax Zmeny" : 'Cinemax Zmeny' }}</title>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{asset('images/logo_cinemax_icon_cropped.png')}}"/>

    @include('layouts.partials._meta')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Alpine ships with Livewire 3, but Livewire only auto-injects its bundle on pages that
         rendered a component - declared here so every page gets Alpine, not just Livewire ones. --}}
    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('additional')
</head>
