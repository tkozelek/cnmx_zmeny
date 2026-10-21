{{-- How the builder works and what every setting does, in the order a manager needs it.
     The <dialog> chrome it sits in is shared with the history panel - see x-rozpis.dialog. --}}

<x-rozpis.dialog id="rozpis-guide"
                 icon="fa-circle-question"
                 title="Rozpis - návod a nastavenia"
                 subtitle="Ako sa rozpis zostavuje a čo sa kde dá nastaviť.">
    <x-slot:footer>
        <button type="submit"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-brand-500/60 bg-brand-500/10 px-4 text-sm font-semibold text-brand-300 transition hover:bg-brand-500/20">
            Rozumiem
        </button>
    </x-slot:footer>

    <h3 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-400">
        <i class="fa-solid fa-list-ol text-[0.7rem]"></i>
        Postup
    </h3>

    <ol class="flex flex-col gap-4">
        @foreach([
            ['fa-lock', 'Zamkni týždeň', 'Rozpis sa dá zostavovať až po zamknutí týždňa. Zamknutím sa uzavrie zapisovanie - zoznam prihlásených sa už nemení a máš z čoho stavať.'],
            ['fa-plus', 'Pridaj pozície do dňa', 'V spodnej časti dňa vyber pozíciu, prípadne čas nástupu, a potvrď <i class="fa-solid fa-plus text-[0.65rem]"></i>. Tú istú pozíciu môžeš pridať viackrát - vzniknú Bufet 1, Bufet 2, Bufet 3.'],
            ['fa-arrows-up-down-left-right', 'Priraď ľudí', 'Zoznam <strong>Nezaradení</strong> obsahuje všetkých, ktorí sa na daný deň zapísali. Presuň meno myšou na pozíciu, alebo ho vyber z rozbaľovacieho zoznamu <em>- priradiť -</em>. Na dotykovom zariadení použi rozbaľovací zoznam.'],
            ['fa-clock', 'Uprav časy nástupu', 'Klikni na čas pri pozícii. Ak už na pozícii niekto stojí, čas sa zmení aj jemu - rozpis si tak neodporuje.'],
            ['fa-clone', 'Kopíruj medzi dňami', 'Ikonou <i class="fa-solid fa-clone text-[0.65rem]"></i> skopíruješ jednu pozíciu do vybraných dní. Rozbaľovacím zoznamom <em>kopírovať celý deň z…</em> prevezmeš celé rozloženie iného dňa. Kopírovanie nikdy neprepíše to, čo už v dni máš - iba dopĺňa.'],
            ['fa-grip-vertical', 'Zmeň poradie', 'Uchop <i class="fa-solid fa-grip-vertical text-[0.65rem]"></i> a potiahni pozíciu vyššie alebo nižšie. Poradie platí len pre daný deň a len v rámci skupiny - riadok potiahnutý do cudzej skupiny sa vráti späť.'],
            ['fa-triangle-exclamation', 'Doplň neobsadené', 'Pozícia, na ktorej nikto nestojí, je v náhľade aj v Exceli označená ako <strong>neobsadené</strong> a v hlavičke dňa vidíš ich počet. Pozície vedúceho sa do tohto počtu nerátajú - tie sa riešia zvlášť.'],
            ['fa-wand-magic-sparkles', 'AI návrh (voliteľné)', 'Tlačidlom <strong>AI navrhni rozpis</strong> dostaneš čiarkovaný náhľad. Nič sa neuloží, kým návrh nepotvrdíš - jednotlivo <i class="fa-solid fa-check text-[0.65rem] text-emerald-400"></i> alebo naraz.'],
            ['fa-bullhorn', 'Zverejni rozpis', 'Keď je rozpis hotový, klikni na <strong>Zverejniť rozpis</strong>. Až vtedy ho uvidia zamestnanci - v režime len na čítanie. Zverejnenie sa dá kedykoľvek stiahnuť späť.'],
            ['fa-file-excel', 'Stiahni Excel', 'Tlačidlom <strong>Excel</strong> získaš rozpis vo formáte na tlač - štyri dni na stranu, papier A4 naležato. Druhý hárok <em>Zoznam</em> je ten istý týždeň ako plochý zoznam zmien s filtrom na každom stĺpci.'],
        ] as $step)
            <li class="flex gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-950 text-brand-400">
                    <i class="fa-solid {{ $step[0] }} text-xs"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-neutral-100">{{ $loop->iteration }}. {{ $step[1] }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-neutral-400">{!! $step[2] !!}</p>
                </div>
            </li>
        @endforeach
    </ol>

    {{-- The number beside each unplaced name is the one thing on this page whose direction
         nobody can guess, so it gets a block of its own rather than a tooltip. --}}
    <h3 class="mb-3 mt-7 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-400">
        <i class="fa-solid fa-hashtag text-[0.7rem]"></i>
        Čo znamená číslo pri mene
    </h3>

    <div class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
        <p class="text-xs leading-relaxed text-neutral-300">
            Je to jedno číslo, jeden rebríček - <strong>koho tím ešte "dlhuje" ťažký deň, a koho naopak
            už netreba na ďalší naháňať</strong>. Zobrazuje sa dvakrát, s opačným poradím:
        </p>

        <ul class="mt-2.5 flex flex-col gap-1.5 text-xs leading-relaxed text-neutral-400">
            <li>
                V <strong class="text-amber-400">neobľúbenom dni</strong> (napr. piatok) je hore
                <strong class="text-neutral-200">najvyššie číslo</strong> - kto sa mu doteraz vyhýbal, je na
                rade prvý.
            </li>
            <li>
                V <strong class="text-emerald-400">obľúbenom dni</strong> (napr. víkend) je hore
                <strong class="text-neutral-200">najnižšie číslo</strong> - kto ťažké dni už odrobil, dostane
                ako odmenu prednosť aj na tento.
            </li>
        </ul>

        <p class="mt-2.5 text-xs leading-relaxed text-neutral-300">
            Je to len odporúčanie, nikoho nepriradí samo - rozhoduješ ty. To isté číslo vidíš aj v
            rozbaľovacom zozname <em>- priradiť -</em> pri každom mene v zátvorke, takže platí aj na dotykovom
            zariadení. Na obyčajný všedný deň sa nezobrazuje nikde, tam poradie nerozhoduje o ničom.
        </p>

        <p class="mt-2.5 rounded border border-neutral-800 bg-neutral-900/60 px-2.5 py-2 text-[0.7rem] leading-relaxed text-neutral-400">
            <strong class="text-neutral-300">Napríklad:</strong> Janka aj Peter odpracovali po 12 dní. Janka
            mala medzi nimi iba 2 piatky, Peter 6. Petrovo číslo je nižšie - na najbližší piatok je preto na
            rade Janka, ale na najbližšiu sobotu má naopak prednosť Peter, ktorý si ju "odrobil".
        </p>

        <p class="mt-2.5 text-[0.7rem] leading-relaxed text-neutral-400">
            Počíta sa z priradení za posledných {{ $guide['windowWeeks'] }} týždňov a z váh
            jednotlivých dní - obe hodnoty nastavíš v správe kina.
        </p>
    </div>

    <h3 class="mb-3 mt-7 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-400">
        <i class="fa-solid fa-scale-balanced text-[0.7rem]"></i>
        Odporúčané rozdelenie dní
    </h3>

    <div class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
        <p class="text-xs leading-relaxed text-neutral-300">
            Panel nad dňami ukáže pre každého zapísaného, koľko zmien by mal tento týždeň dostať.
            Počíta sa z dvoch vecí, v tomto poradí dôležitosti:
        </p>

        <ul class="mt-2.5 flex flex-col gap-1.5 text-xs leading-relaxed text-neutral-400">
            <li class="flex gap-2">
                <i class="fa-solid fa-1 mt-0.5 text-[0.6rem] text-neutral-400"></i>
                <span><strong class="text-neutral-200">Počet zapísaných dní</strong> - kto je k dispozícii viac,
                    dostane viac. Voľné pozície týždňa sa rozdelia v tomto pomere.</span>
            </li>
            <li class="flex gap-2">
                <i class="fa-solid fa-2 mt-0.5 text-[0.6rem] text-neutral-400"></i>
                <span><strong class="text-neutral-200">Zásluhy</strong> - medzi dvoma rovnako dostupnými ľuďmi
                    nakloní rozdelenie o max. ±25 % v prospech toho, kto pre kino odrobil viac - viac dní
                    a viac tých neobľúbených (piatok sa počíta za 1,6 dňa, víkend za 0,8).</span>
            </li>
        </ul>

        <p class="mt-2.5 rounded border border-neutral-800 bg-neutral-900/60 px-2.5 py-2 text-[0.7rem] leading-relaxed text-neutral-400">
            <strong class="text-neutral-300">Napríklad:</strong> Janka aj Peter sa zapísali na rovnaké
            4 dni tento týždeň. Janka má vyššie zásluhy (odrobila viac dní a viac piatkov), tak jej
            panel odporučí o niečo viac zmien než Petrovi - najviac o štvrtinu.
        </p>

        <p class="mt-2.5 text-[0.7rem] leading-relaxed text-neutral-400">
            Nikdy neodporučí viac dní, než na koľko sa človek zapísal. <strong>0</strong> znamená, že týždeň má
            viac dobrovoľníkov než pozícií. Stĺpec <em>Zaradený</em> je stav pri načítaní stránky - po dávke
            priraďovania obnov stránku. Je to odporúčanie, priraďuješ stále ty.
        </p>
    </div>

    <h3 class="mb-3 mt-7 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-400">
        <i class="fa-solid fa-sliders text-[0.7rem]"></i>
        Nastavenia a kde ich zmeniť
    </h3>

    {{-- Live values, not documented defaults: a guide that prints "5 týždňov" while the cinema
         runs on 8 is worse than no guide at all. --}}
    <div class="flex flex-col gap-4">
        <section class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
            <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-neutral-200">
                <i class="fa-solid fa-calendar-week text-[0.7rem] text-neutral-400"></i>
                Týždeň, zapisovanie a absencie
                <a href="{{ route('team.settings.edit') }}" class="text-[0.7rem] font-semibold text-brand-400 underline hover:text-brand-300">
                    správa kina
                </a>
            </p>

            <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                @foreach($guide['settings'] as [$label, $value, $note])
                    <div>
                        <dt class="text-[0.7rem] font-semibold uppercase tracking-wider text-neutral-400">{{ $label }}</dt>
                        <dd class="text-xs font-bold text-neutral-100">{{ $value }}</dd>
                        <dd class="text-[0.7rem] leading-relaxed text-neutral-400">{{ $note }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
            <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-neutral-200">
                <i class="fa-solid fa-scale-balanced text-[0.7rem] text-neutral-400"></i>
                Váhy dní
                <a href="{{ route('team.settings.edit') }}" class="text-[0.7rem] font-semibold text-brand-400 underline hover:text-brand-300">
                    správa kina
                </a>
            </p>

            <p class="mt-1.5 text-[0.7rem] leading-relaxed text-neutral-400">
                Váha hovorí, aký ťažký je deň na obsadenie - nie aký je dlhý. Ako
                <strong class="text-amber-400">neobľúbený</strong> sa označí deň s váhou
                <strong>nad priemerom tvojho týždňa</strong> (teraz {{ number_format($guide['averageWeight'], 2) }});
                v ňom sa nezaradení zoradia podľa toho, kto takých dní odrobil najmenej. Ako
                <strong class="text-emerald-400">obľúbený</strong> sa označí deň s váhou <strong>pod 1,0</strong>;
                v ňom sa zoradia podľa zásluh - kto odrobil najviac dní a najviac tých neobľúbených.
                V oboch prípadoch sa pri menách zobrazí číslo, vyššie je vyššie v poradí. V bežný deň sa
                zoraďujú podľa abecedy. Ak majú všetky dni rovnakú váhu, neoznačí sa žiadny.
            </p>

            <div class="mt-2.5 grid grid-cols-4 gap-1.5 sm:grid-cols-7">
                @foreach($guide['dayWeights'] as $day)
                    <div @class([
                        'rounded border px-1.5 py-1 text-center',
                        'border-amber-500/40 bg-amber-500/10' => $day['isHard'],
                        'border-neutral-800 bg-neutral-900' => ! $day['isHard'],
                    ])>
                        <p class="text-[0.6rem] font-semibold uppercase tracking-wider text-neutral-400">
                            {{ $day['short'] }}
                        </p>
                        <p @class([
                            'text-xs font-bold tabular-nums',
                            'text-amber-400' => $day['isHard'],
                            'text-neutral-300' => ! $day['isHard'],
                        ])>{{ number_format($day['weight'], 1) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
            <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-neutral-200">
                <i class="fa-solid fa-list-check text-[0.7rem] text-neutral-400"></i>
                Pozície a skupiny
                <a href="{{ route('positions.index') }}" class="text-[0.7rem] font-semibold text-brand-400 underline hover:text-brand-300">
                    správa pozícií
                </a>
            </p>

            <ul class="mt-2 flex flex-col gap-1.5 text-[0.7rem] leading-relaxed text-neutral-400">
                @foreach([
                    ['Názov a skratka', 'Skratka (napr. VED) sa použije všade, kde sa celý názov nezmestí. Nepovinná.'],
                    ['Farba', 'Voliteľná farba pozície v tvare #rrggbb.'],
                    ['Vedúci zmeny', 'Pozícia označená ako vedúci sa netlačí medzi ostatné riadky - vypíše sa raz v hlavičke dňa ako „manažér“ a nepočíta sa medzi neobsadené.'],
                    ['Skupina', 'Vlastné skupiny (Bufet, Uvádzači, Manažment…) rozhodujú, ktoré pozície idú v rozpise aj v Exceli za sebou. Pozícia môže zostať aj bez skupiny - také idú nakoniec. Zmazanie skupiny pozície nezmaže, iba ich odradí.'],
                    ['Poradie', 'Šípkami zmeníš poradie pozícií aj skupín. Je to predvolené poradie riadkov v každom dni.'],
                    ['Aktívna / neaktívna', 'Neaktívna pozícia sa už nedá pridať do nového dňa, ale historické priradenia zostávajú. Pozície sa preto nemažú, iba deaktivujú.'],
                ] as [$label, $note])
                    <li class="flex gap-2">
                        <i class="fa-solid fa-angle-right mt-1 text-[0.6rem] text-neutral-400"></i>
                        <span><strong class="text-neutral-200">{{ $label }}</strong> - {{ $note }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-rozpis.dialog>
