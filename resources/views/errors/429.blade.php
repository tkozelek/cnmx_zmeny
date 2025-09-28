<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Príliš veľa požiadaviek</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen p-4">
<div class="text-center bg-gray-800 rounded-lg shadow-xl p-8 md:p-10 border border-gray-700 max-w-xl w-full">
    <h1 class="text-7xl md:text-9xl font-extrabold text-orange-400 mb-4 tracking-tight">
        429
    </h1>
    <p class="text-3xl md:text-4xl font-bold text-white mb-4">
        Príliš veľa požiadaviek
    </p>
    <p class="text-lg md:text-xl text-slate-300 mb-8">
        Spomaľte, prosím. Zaznamenali sme príliš veľa požiadaviek z vašej strany. Skúste to znova o <b>1 minútu</b>.
    </p>
    <a href="/" class="inline-block bg-green-300 hover:bg-green-600 text-gray-900 font-semibold py-3 px-6 rounded-lg transition-colors duration-300 shadow-md transform hover:scale-105">
        Prejsť na domovskú stránku
    </a>
    <p class="mt-6 text-sm text-slate-400/80">
        Ak si myslíte, že ide o chybu, prosím, <a href="{{ route('bugreport.index') }}" class="text-red-300 hover:text-red-400 underline transition-colors duration-150">kontaktujte podporu</a>.
    </p>
</div>
</body>
</html>
