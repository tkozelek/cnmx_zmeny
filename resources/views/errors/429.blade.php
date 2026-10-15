@extends('errors::minimal')

@php
    $retryAfterSeconds = (int) ($exception?->getHeaders()['Retry-After'] ?? 0);
    $retryAfter = $retryAfterSeconds >= 60 ? ceil($retryAfterSeconds / 60).' min' : $retryAfterSeconds.' s';
@endphp

@section('code', '429')
@section('title', 'Príliš veľa pokusov')
@section('caption', 'Plná sála')
@section('heading', 'Príliš veľa pokusov')
@section('message')
    Za krátky čas prišlo od teba priveľa požiadaviek.
    @if($retryAfterSeconds > 0)
        Skús to znova o <strong>{{ $retryAfter }}</strong>.
    @else
        Chvíľu počkaj a skús to znova.
    @endif
@endsection

@section('actions')
    <a href="{{ url()->previous() }}" class="button button-primary">
        Vrátiť sa späť
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
    </a>
    <a href="{{ url('/') }}" class="button button-secondary">Prejsť na úvod</a>
@endsection
