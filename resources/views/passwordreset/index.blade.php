<x-layout title="Zabudnuté heslo" description="Obnov si zabudnuté heslo do účtu Cinemax Zmeny.">
    <x-auth-card title="Zabudnuté heslo" subtitle="Zadaj svoj e-mail a pošleme ti odkaz na nastavenie nového hesla.">
        <form class="flex flex-col gap-6" action="{{ route('password.send') }}" method="POST">
            @csrf

            <x-form-input
                type="email"
                name="email"
                autocomplete="email"
                spellcheck="false"
                label="E-mail"
                placeholder="tvoj@email.sk"
                :value="old('email')"
                icon="fa-envelope"
                required
                autofocus
            />

            <x-submit-button busy-label="Posielam…">Poslať odkaz</x-submit-button>
        </form>

        <x-slot:footer>
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center px-1 font-semibold text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">Späť na prihlásenie</a>
        </x-slot:footer>
    </x-auth-card>
</x-layout>
