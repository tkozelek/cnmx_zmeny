<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Účet schválený</title>
</head>
<body>
<h1>Dobrý deň, {{ $user->name }},</h1>
<p>Manažér kina schválil váš účet. Môžete sa prihlásiť.</p>

<a href="{{ route('login') }}" style="
        display: inline-block;
        padding: 10px 20px;
        color: white;
        background-color: #1a73e8;
        text-decoration: none;
        border-radius: 5px;
    ">Prihlásiť sa</a>

<p>S pozdravom,<br>Cine-max Zmeny</p>
</body>
</html>
