<x-layout title="Nové heslo" description="Nastav si nové heslo do účtu Cinemax Zmeny.">
    <x-auth-card title="Nové heslo" subtitle="Zadaj nové heslo pre svoj účet.">
        <form class="flex flex-col gap-6" action="{{ route('password.store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            @if(session('error'))
                <x-alert>{{ session('error') }}</x-alert>
            @endif

            <x-form-input
                type="email"
                name="email"
                autocomplete="username"
                spellcheck="false"
                label="E-mail"
                :value="$email ?: old('email')"
                icon="fa-envelope"
                required
            />

            <x-form-input
                type="password"
                name="password"
                autocomplete="new-password"
                label="Nové heslo"
                icon="fa-key"
                required
                autofocus
            />

            <x-form-input
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                label="Nové heslo znova"
                icon="fa-check-double"
                required
            />

            <x-submit-button busy-label="Ukladám…">Uložiť nové heslo</x-submit-button>
        </form>

        <x-slot:footer>
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center px-1 font-semibold text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">Späť na prihlásenie</a>
        </x-slot:footer>
    </x-auth-card>
</x-layout>
