{{-- Inline CSS, no @vite and no auth()/DB calls: a 500/503 page must still render when the build, database or session is what broke. --}}
@php
    $previousUrl = url()->previous();
    $hasPreviousPage = $previousUrl !== url()->current() && rtrim($previousUrl, '/') !== rtrim(url('/'), '/');
@endphp
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark">
    <title>@yield('code') – @yield('title') | Cinemax Zmeny</title>
    <link rel="icon" href="{{ asset('images/logo_cinemax_icon_cropped.png') }}">

    <style>
        :root {
            --bg: #0a0a0a;         
            --surface: #171717;    
            --line: #262626;       
            --line-strong: #404040;
            --text: #e5e5e5;       
            --muted: #a3a3a3;      
            --brand-300: 125 211 252;
            --brand-400: 56 189 248;
            --ease-out-soft: cubic-bezier(0.16, 1, 0.3, 1);
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter Variable', 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a { color: inherit; text-decoration: none; }

        a:focus-visible {
            outline: 2px solid rgb(var(--brand-400));
            outline-offset: 2px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .topbar {
            border-bottom: 1px solid rgb(38 38 38 / 0.8);
            background: rgb(23 23 23 / 0.9);
        }

        .topbar-inner {
            max-width: 80rem;
            margin: 0 auto;
            padding: 0.875rem 1rem;
        }

        .wordmark {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #fff;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem 4rem;
        }

        .stage {
            width: 100%;
            max-width: 34rem;
        }

        .screen {
            position: relative;
            aspect-ratio: 2.39 / 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            background: #141414;
            box-shadow: 0 64px 56px -56px rgb(var(--brand-400) / 0.22);
        }

        .screen-code {
            font-size: clamp(3.25rem, 15vw, 6rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            font-variant-numeric: tabular-nums;
            color: #f5f5f5;
        }

        .screen-caption {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .seats {
            display: grid;
            gap: 0.5rem;
            justify-items: center;
            margin-top: 1.75rem;
        }

        .row {
            display: grid;
            grid-template-columns: repeat(var(--seats), 1fr);
            column-gap: 0.3rem;
            width: var(--width);
        }

        .seat {
            height: 0.85rem;
            border-radius: 0.35rem 0.35rem 0.1rem 0.1rem;
            background: var(--seat-color);
            box-shadow: inset 0 1px 0 rgb(var(--brand-300) / var(--seat-light));
        }

        .seat.aisle { visibility: hidden; }

        .row:nth-child(1) { --seat-color: #333333; --seat-light: 0.3; --width: 78%; }
        .row:nth-child(2) { --seat-color: #262626; --seat-light: 0.15; --width: 89%; }
        .row:nth-child(3) { --seat-color: #1f1f1f; --seat-light: 0.06; --width: 100%; }

        .copy { margin-top: 2.75rem; }

        h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.01em;
            color: #fff;
            text-wrap: balance;
        }

        .message {
            margin: 0.75rem 0 0;
            max-width: 32rem;
            font-size: 1rem;
            color: var(--muted);
            text-wrap: pretty;
        }

        .message strong { color: var(--text); font-weight: 600; }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 48px;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            white-space: nowrap;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .button-primary { background: #f5f5f5; color: #171717; }
        .button-primary:hover { background: #fff; }

        .button-secondary {
            border: 1px solid var(--line-strong);
            background: var(--surface);
            color: #f5f5f5;
        }

        .button-secondary:hover { border-color: #525252; background: var(--line); }

        .button svg { width: 1rem; height: 1rem; flex: none; }

        .hint {
            margin: 2.5rem 0 0;
            padding-top: 1.25rem;
            border-top: 1px solid var(--line);
            font-size: 0.875rem;
            color: var(--muted);
        }

        @media (min-width: 640px) {
            main { padding-top: 4rem; }
            .topbar-inner { padding-inline: 1.5rem; }
            h1 { font-size: 2rem; }
            .seat { height: 1rem; }
        }

        @media (max-width: 400px) {
            .button { flex: 1 1 100%; }
        }

        @media (prefers-reduced-motion: no-preference) {
            .screen-code, .screen-caption {
                animation: projector-on 0.9s steps(1, end) both;
            }

            .screen {
                animation: light-spill 1.4s var(--ease-out-soft) both;
            }

            .copy {
                animation: rise 0.8s var(--ease-out-soft) 0.25s both;
            }

            @keyframes projector-on {
                0% { opacity: 0; }
                20% { opacity: 0.55; }
                30% { opacity: 0.1; }
                45% { opacity: 0.85; }
                55% { opacity: 0.4; }
                70%, 100% { opacity: 1; }
            }

            @keyframes light-spill {
                from { box-shadow: 0 64px 56px -56px rgb(var(--brand-400) / 0); }
            }

            @keyframes rise {
                from { opacity: 0; transform: translateY(12px); }
            }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ url('/') }}" class="wordmark">Cinemax</a>
    </div>
</header>

<main>
    <div class="stage">
        <div aria-hidden="true">
            <div class="screen">
                <div class="screen-code">@yield('code')</div>
                @hasSection('caption')
                    <div class="screen-caption">@yield('caption')</div>
                @endif
            </div>

            <div class="seats">
                @foreach([12, 14, 16] as $seatCount)
                    <div class="row" style="--seats: {{ $seatCount + 1 }}">
                        @for($seat = 0; $seat <= $seatCount; $seat++)
                            <span @class(['seat', 'aisle' => $seat === intdiv($seatCount, 2)])></span>
                        @endfor
                    </div>
                @endforeach
            </div>
        </div>

        <div class="copy">
            <h1><span class="sr-only">Chyba @yield('code'): </span>@yield('heading')</h1>
            <p class="message">@yield('message')</p>

            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ url('/') }}" class="button button-primary">
                        Prejsť na úvod
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                    </a>
                    @if($hasPreviousPage)
                        <a href="{{ $previousUrl }}" class="button button-secondary">Vrátiť sa späť</a>
                    @endif
                @endif
            </div>

            @hasSection('hint')
                <p class="hint">@yield('hint')</p>
            @endif
        </div>
    </div>
</main>
</body>
</html>
