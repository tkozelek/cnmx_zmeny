@extends('errors::minimal')

@section('code', '401')
@section('title', 'Neprihlásený')
@section('caption', 'Vstup len s lístkom')
@section('heading', 'Najprv sa prihlás')
@section('message', 'Táto stránka je dostupná iba prihláseným používateľom.')

@section('actions')
    <a href="{{ route('login') }}" class="button button-primary">
        Prihlásiť sa
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
    </a>
    <a href="{{ url('/') }}" class="button button-secondary">Prejsť na úvod</a>
@endsection
