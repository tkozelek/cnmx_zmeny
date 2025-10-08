@php
    $mobile = 'w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800 flex justify-center';
    $desktop = 'px-4 py-2 mt-2 text-sm font-semibold rounded-lg hover:bg-gray-700 md:mt-0 md:ml-2 transition-colors duration-200 flex items-center';
    $classes = $isMobile ? $mobile : $desktop;
    $classes = $classes.($activeClass && request()->routeIs($route) ? ' '.$activeClass : '');

@endphp

<a {{ $attributes->merge([
        'class' => $classes
    ]) }}
   href="{{ route($route) }}">

    @if($icon)
        <div class="mr-2">
            {!! $icon !!}
        </div>
    @endif

    <span class="">{{ $slot }}</span>
</a>
