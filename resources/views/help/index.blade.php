<x-layout title="POMOC">
    <div class="container mx-auto px-4 py-12 md:py-16">

        <div x-data="{ activeAccordion: null }" class="w-full max-w-4xl mx-auto bg-neutral-900/80 rounded-2xl border border-neutral-800 divide-y divide-neutral-800 shadow-2xl overflow-hidden">

            {{-- 1. Registrácia --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 1 ? null : 1)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-user-plus text-sky-400"></i>
                            Registrácia
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 1 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 1" style="display: none;">

                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800/80 bg-neutral-950/40 space-y-5">
                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 1: Vyplnenie registračného formulára</h3>
                            <p class="mb-3 text-sm">Pre registráciu je potrebné vyplniť nasledujúce údaje:</p>
                            <ul class="list-disc list-inside space-y-1.5 text-sm text-neutral-300 pl-2">
                                <li><strong class="font-semibold text-white">Meno</strong>: Zadajte svoje meno.</li>
                                <li><strong class="font-semibold text-white">Priezvisko</strong>: Zadajte svoje priezvisko.</li>
                                <li><strong class="font-semibold text-white">Email</strong>: Zadajte svoj email, ktorý bude slúžiť ako prihlasovacie meno.</li>
                                <li><strong class="font-semibold text-white">Heslo</strong>: Vytvorte si heslo k účtu.</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 2: Čakanie na schválenie</h3>
                            <p class="text-sm">Po vyplnení bude registrácia odoslaná na schválenie manažérom alebo administrátorom kina.</p>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 3: Prihlásenie</h3>
                            <p class="text-sm">Po schválení sa môžete ihneď prihlásiť a zapisovať na zmeny.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Zapisovanie na zmeny --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 2 ? null : 2)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-calendar-days text-sky-400"></i>
                            Zapisovanie na zmeny
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 2 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 2" style="display: none;">
                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800/80 bg-neutral-950/40 space-y-4 text-sm">
                        <p>Zapisovanie na zmeny funguje pomocou tlačidla <strong class="font-semibold text-sky-400">"Zapísať"</strong> (prípadne <strong class="font-semibold text-sky-400">"Odpísať"</strong>). Po stlačení sa vaše meno zobrazí pri danom dni.</p>
                        <p>Môžete tiež využiť textové pole <strong class="font-semibold text-white">Extra Info</strong> pre spresnenie vašich časových možností (napr. <em>od 15:00</em> alebo <em>do 22:00</em>).</p>
                        <p>Plánovať a zapisovať sa môžete na viacero týždňov dopredu podľa nastavení kina.</p>
                    </div>
                </div>
            </div>

            {{-- 3. Absencie a dovolenky --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 3 ? null : 3)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-umbrella-beach text-sky-400"></i>
                            Absencie a dovolenky
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 3 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 3" style="display: none;">
                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800/80 bg-neutral-950/40 space-y-4 text-sm">
                        <p>Ak v niektoré dni nemôžete pracovať, nahláste si absenciu v sekcii <strong class="font-semibold text-white">Absencie</strong>.</p>
                        <p>Pre nahlásenie vyberte rozsah dátumov a uveďte dôvod. Počas nahlásenej absencie vás systém nepustí prihlásiť sa na zmeny v dané dni.</p>
                    </div>
                </div>
            </div>

            {{-- 4. Evidencia hodín --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 4 ? null : 4)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-clock text-sky-400"></i>
                            Evidencia odpracovaných hodín
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 4 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 4" style="display: none;">
                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800/80 bg-neutral-950/40 space-y-4 text-sm">
                        <p>V sekcii <strong class="font-semibold text-white">Evidencia hodín</strong> vidíte mesačný prehľad odpracovaných zmien a vypočítanú mzdu.</p>
                        <p>Hodiny si môžete dopĺňať alebo upravovať za každý odpracovaný deň.</p>
                    </div>
                </div>
            </div>
            {{-- 5. Správa pre Administrátorov --}}
            @if(auth()->user() && auth()->user()->hasRole('admin'))
                <div>
                    <h2>
                        <button
                            type="button"
                            @click="activeAccordion = (activeAccordion === 5 ? null : 5)"
                            class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                        >
                            <span class="flex items-center gap-3 text-lg">
                                <i class="fa-solid fa-shield-halved text-sky-400"></i>
                                Administrácia kina
                            </span>
                            <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 5 }"></i>
                        </button>
                    </h2>
                    <div x-show="activeAccordion === 5" style="display: none;">
                        <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800/80 bg-neutral-950/40 space-y-4 text-sm">
                            <h3 class="text-lg font-bold text-neutral-100">Spravovanie používateľov</h3>
                            <ul class="list-disc list-inside space-y-1.5 text-neutral-300 pl-2">
                                <li><strong>Vytvorenie používateľa</strong>: Odošle pozvánku / reset hesla na zadaný email.</li>
                                <li><strong>Správa členov</strong>: Možnosť schvaľovať žiadosti o vstup do kina a meniť roly.</li>
                            </ul>

                            <h3 class="text-lg font-bold text-neutral-100 pt-2">Spravovanie zmien & súborov</h3>
                            <ul class="list-disc list-inside space-y-1.5 text-neutral-300 pl-2">
                                <li><strong>Zamykanie týždňov</strong>: Zamykanie prihlasovania pre zvolené týždne.</li>
                                <li><strong>Excel export</strong>: Stiahnutie kompletného rozpisu služieb.</li>
                                <li><strong>Správa dokumentov</strong>: Nahrávanie a zdieľanie súborov pre zamestnancov.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>
</x-layout>

