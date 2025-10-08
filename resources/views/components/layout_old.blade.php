<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title', 'CINE-MAX ZMENY')</title>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{ asset('images/logo_cinemax_icon_cropped.png') }}"/>

    <meta name="robots" content="noindex">
    <meta name="description" content="Webová aplikácia pre zamestnancov Cine-maxu na zapisovanie pracovných zmien.">
    <meta name="keywords" content="cine-max, brigáda, práca, zapisovanie, absencia, peniaze, pracovný rozpis, rozpis">
    <meta name="author" content="T. Kozelek">

    <meta property="og:title" content="Cine-max Zmeny">
    <meta property="og:description" content="Zapíš sa na deň a uži si pracovný deň.">
    <meta property="og:image" content="{{ asset('images/logo_cinemax_icon_cropped.png') }}">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:type" content="website">

    <!-- DNS Prefetch (fallback pre staršie prehliadače) -->
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://ajax.googleapis.com">

    <!-- Preconnect pre rýchlejšie spojenie -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://ajax.googleapis.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
          integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer"
          fetchpriority="low">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.css"
          integrity="sha512-7uSoC3grlnRktCWoO4LjHMjotq8gf9XDFQerPuaph+cqR7JC9XKGdvN+UwZMC14aAaBDItdRj3DcSDs4kMWUgg=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer"
          fetchpriority="low">

    <!-- Scripts -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-gray-700  text-gray-200">

    @include('partials.navigation')

<main>
    <div>
        {{ $slot }}
    </div>

</main>
    <x-flash-message />

<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js" integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    let addUserUrl = "{{ asset('/adduser') }}";
    let renderUsers = "{{ asset('/renderUsers') }}";
</script>
</body>
</html>


