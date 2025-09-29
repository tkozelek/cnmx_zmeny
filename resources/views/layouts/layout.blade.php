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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js" integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        let addUserUrl = "{{ route('calendar.toggleUser') }}";
    </script>
</body>
</html>
