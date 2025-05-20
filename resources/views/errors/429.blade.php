@extends('errors::minimal')

@section('title', __('auth.throttle', ['seconds' => 60]))
@section('code', '429')
@section('message', __('auth.throttle', ['seconds' => 60]))
