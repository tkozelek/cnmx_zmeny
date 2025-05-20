@props(['title'])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title', config('app.name', 'CINEMAX ZMENY'))</title>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{asset('images/logo_cinemax_icon_cropped.png')}}"/>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.css" integrity="sha512-7uSoC3grlnRktCWoO4LjHMjotq8gf9XDFQerPuaph+cqR7JC9XKGdvN+UwZMC14aAaBDItdRj3DcSDs4kMWUgg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-700">

    <div class="w-full text-gray-200 bg-slate-900">
        <div x-data="{ open: false }" class="flex flex-col container px-4 mx-auto md:items-center md:justify-between md:flex-row md:px-6 lg:px-8">
            <div class="p-2 flex flex-row items-center justify-between space-x-2 my-1">
                <a href="{{ url('/') }}" class="text-xl font-semibold tracking-wider uppercase rounded-lg text-white focus:outline-none focus:shadow-outline">
                    CINEMAX
                </a>
                <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" @click="open = !open">
                    <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                        <path x-show="!open" fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        <path x-show="open" fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <nav :class="{'flex': open, 'hidden': !open}" class="flex-col flex-grow pb-4 md:pb-0 hidden md:flex md:justify-end md:flex-row">
                <a class="px-4 py-2 mt-2 text-sm font-semibold rounded-lg focus:text-black hover:text-white text-gray-200 md:mt-0 hover:bg-gray-600 border-gray-200 md:border-none focus:bg-gray-200 focus:outline-none focus:shadow-outline" href="{{route('help')}}">
                    <i class="fa-solid w-6 fa-circle-question"></i>Pomoc
                </a>
                <hr>
                @auth()
                    @include('layouts.partials._user-dropdown')
                @else
                    @include('layouts.partials._guest')
                @endauth
            </nav>
        </div>
    </div>
<main>
    <div>
        {{$slot}}
    </div>

</main>
    <x-flash-message />

<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js" integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    let addUserUrl = "{{ asset('/adduser') }}";
    let renderUsers = "{{ asset('/renderUsers') }}";
</script>
@vite(['resources/js/app.js'])
</body>
</html>


