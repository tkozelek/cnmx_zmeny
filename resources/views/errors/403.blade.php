@extends('errors::minimal')

{{-- A refusal that bothered to explain itself says why. Several aborts in the app pass a
     Slovak sentence (abort(403, '...'), Response::deny('...')) and none of them used to be
     shown. Laravel's own default message is English and means "no reason given", so it
     falls through to the generic line rather than being printed at a user. --}}
@php($reason = trim($exception?->getMessage() ?? ''))

@section('code', '403')
@section('title', 'Prístup zamietnutý')
@section('caption', 'Súkromné premietanie')
@section('heading', 'Sem nemáš prístup')
@section('message', $reason !== '' && $reason !== 'This action is unauthorized.'
    ? $reason
    : 'Na zobrazenie tejto stránky alebo vykonanie tejto akcie nemáš oprávnenie.')
@section('hint', 'Ak si myslíš, že ide o chybu, obráť sa na manažéra kina.')
