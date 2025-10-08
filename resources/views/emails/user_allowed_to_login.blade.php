<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Účet overený</title>
</head>
<body>
<h1>Ahoj {{ $user->name }},</h1>
<p>Tvoj účet bol overený! Teraz sa môžeš prihlásiť do aplikácie.</p>

<a href="{{ route('login') }}" style="
        display: inline-block;
        padding: 10px 20px;
        color: white;
        background-color: #1a73e8;
        text-decoration: none;
        border-radius: 5px;
    ">Prihlás sa</a>

<p>S pozdravom,<br>{{ config('app.name') }}</p>
</body>
</html>
