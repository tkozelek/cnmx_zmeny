<x-layout title="Zmena e-mailu" description="Potvrdenie zmeny e-mailovej adresy účtu.">
    <div class="flex w-full flex-1 items-center justify-center px-4 py-12 md:py-20">
        <div class="w-full max-w-lg space-y-6 rounded-lg border border-neutral-800 bg-neutral-900 p-8 text-center shadow-sm sm:p-10">
            @if($outcome)
                <div @class([
                    'mx-auto inline-flex size-14 items-center justify-center rounded-lg border text-2xl',
                    'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' => ! $outcome['failed'],
                    'border-rose-500/20 bg-rose-500/10 text-rose-400' => $outcome['failed'],
                ])>
                    <i class="fa-solid {{ $outcome['icon'] }}" aria-hidden="true"></i>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-white">{{ $outcome['title'] }}</h1>
                    <p class="text-sm leading-relaxed text-neutral-300 sm:text-base">{{ $outcome['text'] }}</p>
                </div>

                <a href="{{ route('login') }}" class="flex min-h-12 w-full items-center justify-center rounded-lg bg-neutral-100 px-6 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white">
                    Prejsť do aplikácie
                </a>

                @if($canStillDecline)
                    <form action="{{ request()->fullUrl() }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 px-6 py-3 text-base font-semibold text-neutral-100 transition hover:bg-neutral-700">
                            Zmenu predsa zamietnuť
                        </button>
                    </form>
                @endif
            @else
                <div class="mx-auto inline-flex size-14 items-center justify-center rounded-lg border border-amber-500/20 bg-amber-500/10 text-2xl text-amber-400">
                    <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-white">Zmeniť e-mail účtu?</h1>
                    <p class="text-sm leading-relaxed text-neutral-300 sm:text-base">
                        Účet <strong class="text-white">{{ $user->getFilamentName() }}</strong> sa má namiesto
                        <strong class="break-all text-white">{{ $user->email }}</strong> prihlasovať adresou
                        <strong class="break-all text-white">{{ $user->pending_email }}</strong>.
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <form action="{{ request()->fullUrl() }}" method="POST">
                        @csrf
                        <x-submit-button busy-label="Potvrdzujem…">Áno, potvrdiť zmenu</x-submit-button>
                    </form>
                    <form action="{{ request()->fullUrl() }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 px-6 py-3 text-base font-semibold text-neutral-100 transition hover:bg-neutral-700">
                            Nie, zamietnuť zmenu
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layout>
