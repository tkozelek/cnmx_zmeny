<head>
    {{-- Lets CSS hide below-the-fold scroll-reveal content only when JS is there to reveal it again -
         and shows it anyway if the welcome script hasn't started within 4 s (blocked, offline, stale
         build). The hero above the fold animates in with CSS alone and never waits for this. --}}
    <script>
        document.documentElement.classList.add('js');
        setTimeout(function () {
            var root = document.documentElement;
            if (!root.classList.contains('motion-ready')) root.classList.remove('js');
        }, 4000);
        // The calendar's saved switches, before the first paint - set later, names flashed in and out.
        // app.js re-syncs them with the checkboxes (syncCalendarToggles).
        try {
            if (localStorage.getItem('names_checkbox') === '0') document.documentElement.classList.add('hide-names');
            if (localStorage.getItem('draw_checkbox') === '1') document.documentElement.classList.add('show-draw');
        } catch (e) { /* storage blocked - defaults */ }
    </script>
    <title>{{ $title ? "{$title} | Cinemax Zmeny" : 'Cinemax Zmeny' }}</title>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0c0c0e">

    @foreach($fontPreloads as $fontUrl)
        <link rel="preload" href="{{ $fontUrl }}" as="font" type="font/woff2" crossorigin>
    @endforeach

    @include('layouts.partials._meta')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Alpine ships with Livewire 3, but Livewire only auto-injects its bundle on pages that
         rendered a component - declared here so every page gets Alpine, not just Livewire ones. --}}
    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
