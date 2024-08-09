<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bol si overený!</title>
</head>
<body>
    #Ahoj {{$user->name}}!

    S radosťou vás informujeme, že sa teraz môžete prihlásiť do svojho účtu.


    <x-button :url="route('login')" :color="'primary'">
        Prihlásenie
    </x-button>

    S pozdravom,
    Tím {{ config('app.name') }}
</body>
</html>

