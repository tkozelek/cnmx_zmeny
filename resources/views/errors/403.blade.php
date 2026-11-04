@extends('errors::minimal')

@section('code', '403')
@section('title', 'Prístup zamietnutý')
@section('caption', 'Súkromné premietanie')
@section('heading', 'Sem nemáš prístup')
{{-- A refusal that explains itself (abort(403, '...'), Response::deny('...')) says why - see ErrorPageComposer. --}}
@section('message', $reason ?? 'Na zobrazenie tejto stránky alebo vykonanie tejto akcie nemáš oprávnenie.')
@section('hint', 'Ak si myslíš, že ide o chybu, obráť sa na manažéra kina.')
