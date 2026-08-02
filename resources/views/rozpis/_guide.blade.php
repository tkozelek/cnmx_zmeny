{{-- How the builder works, in the order a manager actually does it. In a modal rather than on the
     page because it is read once and then never again. Opened from the header button. --}}
<x-modal name="rozpis-guide" maxWidth="2xl">
    <div class="flex items-start justify-between gap-4 border-b border-neutral-800 px-6 py-4">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                <i class="fa-solid fa-circle-question text-sky-400"></i>
                Ako zostaviť rozpis
            </h2>
            <p class="mt-1 text-xs text-neutral-400">Stručný návod krok za krokom.</p>
        </div>

        <button type="button" x-on:click="show = false"
                class="shrink-0 rounded-lg p-2 text-neutral-500 transition hover:bg-neutral-800 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
        <ol class="flex flex-col gap-4">
            @foreach([
                ['fa-lock', 'Zamknite týždeň', 'Rozpis sa dá zostavovať až po zamknutí týždňa. Zamknutím sa uzavrie zapisovanie — zoznam prihlásených sa už nemení a máte z čoho stavať.'],
                ['fa-plus', 'Pridajte pozície do dňa', 'V spodnej časti dňa vyberte pozíciu, prípadne čas nástupu, a potvrďte <i class="fa-solid fa-plus text-[0.65rem]"></i>. Tú istú pozíciu môžete pridať viackrát — vzniknú Bufet 1, Bufet 2, Bufet 3.'],
                ['fa-arrows-up-down-left-right', 'Priraďte ľudí', 'Zoznam <strong>Nezaradení</strong> obsahuje všetkých, ktorí sa na daný deň zapísali. Presuňte meno myšou na pozíciu, alebo ho vyberte z rozbaľovacieho zoznamu <em>— priradiť —</em>. Na dotykovom zariadení použite rozbaľovací zoznam.'],
                ['fa-clock', 'Upravte časy nástupu', 'Kliknite na čas pri pozícii. Ak už na pozícii niekto stojí, čas sa zmení aj jemu — rozpis si tak neodporuje.'],
                ['fa-clone', 'Kopírujte medzi dňami', 'Ikonou <i class="fa-solid fa-clone text-[0.65rem]"></i> skopírujete jednu pozíciu do vybraných dní. Rozbaľovacím zoznamom <em>kopírovať celý deň z…</em> prevezmete celé rozloženie iného dňa. Kopírovanie nikdy neprepíše to, čo už v dni máte — iba dopĺňa.'],
                ['fa-grip-vertical', 'Zmeňte poradie', 'Uchopte <i class="fa-solid fa-grip-vertical text-[0.65rem]"></i> a potiahnite pozíciu vyššie alebo nižšie. Poradie platí len pre daný deň.'],
                ['fa-wand-magic-sparkles', 'AI návrh (voliteľné)', 'Tlačidlom <strong>AI navrhni rozpis</strong> dostanete čiarkovaný náhľad. Nič sa neuloží, kým návrh nepotvrdíte — jednotlivo <i class="fa-solid fa-check text-[0.65rem] text-emerald-400"></i> alebo naraz.'],
                ['fa-bullhorn', 'Zverejnite rozpis', 'Keď je rozpis hotový, kliknite na <strong>Zverejniť rozpis</strong>. Až vtedy ho uvidia zamestnanci — v režime len na čítanie. Zverejnenie sa dá kedykoľvek stiahnuť späť.'],
                ['fa-file-excel', 'Stiahnite Excel', 'Tlačidlom <strong>Excel</strong> získate rozpis vo formáte na tlač — štyri dni na stranu, papier A4 naležato.'],
            ] as $step)
                <li class="flex gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-950 text-sky-400">
                        <i class="fa-solid {{ $step[0] }} text-xs"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-neutral-100">{{ $loop->iteration }}. {{ $step[1] }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-neutral-400">{!! $step[2] !!}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-5 rounded-lg border border-amber-500/30 bg-amber-500/5 px-4 py-3">
            <p class="text-xs leading-relaxed text-amber-200/90">
                <i class="fa-solid fa-triangle-exclamation mr-1 text-[0.7rem]"></i>
                <strong>Neobľúbený deň</strong> je deň, na ktorý sa málokto hlási dobrovoľne. Pri takom dni je
                zoznam nezaradených zoradený podľa toho, kto je na ťahu — číslo pri mene je poradie
                spravodlivosti, vyššie znamená skôr. Váhy dní si nastavíte v
                <a href="{{ route('team.settings.edit') }}" class="font-semibold underline hover:text-amber-100">správe kina</a>.
            </p>
        </div>
    </div>

    <div class="flex justify-end border-t border-neutral-800 px-6 py-4">
        <button type="button" x-on:click="show = false"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-sky-500/40 bg-sky-500/10 px-4 text-sm font-semibold text-sky-300 transition hover:bg-sky-500/20">
            Rozumiem
        </button>
    </div>
</x-modal>
