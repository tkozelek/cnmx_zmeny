{{-- Not indexed yet on purpose - the tags that actually matter now are og:*/twitter:*, since
     those are what a link-preview bot (Slack, WhatsApp, iMessage, Twitter/X, ...) reads. Every
     page passes title/description via <x-layout>; unset ones fall back to the defaults below. --}}
<meta name="robots" content="noindex"/>
<meta name="description" content="{{ $description ?? 'Webová aplikácia pre zamestnancov Cine-maxu na zapisovanie pracovných zmien.' }}"/>
<meta name="author" content="T. Kozelek"/>

<meta property="og:title" content="{{ $title ?? 'Cine-max Zmeny' }}"/>
<meta property="og:description" content="{{ $description ?? 'Zapíš sa na deň a uži si pracovný deň.' }}"/>
<meta property="og:image" content="{{ $ogImage ?? asset('images/cinemax_landing_page_upscaled.jpg') }}"/>
<meta property="og:url" content="{{ url()->current() }}"/>
<meta property="og:type" content="website"/>
<meta property="og:locale" content="sk_SK"/>

<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:title" content="{{ $title ?? 'Cine-max Zmeny' }}"/>
<meta name="twitter:description" content="{{ $description ?? 'Zapíš sa na deň a uži si pracovný deň.' }}"/>
<meta name="twitter:image" content="{{ $ogImage ?? asset('images/cinemax_landing_page_upscaled.jpg') }}"/>
