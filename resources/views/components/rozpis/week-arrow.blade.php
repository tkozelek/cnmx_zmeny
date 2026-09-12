{{--
    One step back or forward through the weeks, in the rozpis header.

    The builder and the published view each render a pair of these, so the chrome lived in four
    places. x-date solves the same problem for the calendar but hardcodes the calendar.show route,
    which is exactly what the builder must not link to - so this takes the href instead.
--}}
@props(['href', 'direction' => 'next'])

@php($isPrevious = $direction === 'previous')

<a href="{{ $href }}"
   title="{{ $isPrevious ? 'Predchádzajúci týždeň' : 'Nasledujúci týždeň' }}"
   class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-200 transition hover:border-neutral-700 hover:text-white">
    <i class="fa-solid fa-chevron-{{ $isPrevious ? 'left' : 'right' }}"></i>
</a>
