<x-layout title="Pomoc" description="Časté otázky o registrácii, zapisovaní na dni a absenciách v Cine-max Zmeny.">
    <div class="container mx-auto px-4 py-12 md:py-16">

        <div x-data="{ activeAccordion: null }" class="w-full max-w-4xl mx-auto bg-neutral-900 rounded-lg border border-neutral-800 divide-y divide-neutral-800 shadow-sm overflow-hidden">

            {{-- 1. Registrácia --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 1 ? null : 1)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-user-plus text-brand-400"></i>
                            Registrácia
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 1 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 1" style="display: none;">

                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800 bg-neutral-950 space-y-5">
                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 1: Registrácia</h3>
                            <p class="mb-3 text-sm">Vyplňte registračný formulár. Ak aplikáciu používa viac kín, vyberte aj svoje kino.</p>
                            <ul class="list-disc list-inside space-y-1.5 text-sm text-neutral-300 pl-2">
                                <li><strong class="font-semibold text-white">Meno</strong>: Zadajte svoje meno.</li>
                                <li><strong class="font-semibold text-white">Priezvisko</strong>: Zadajte svoje priezvisko.</li>
                                <li><strong class="font-semibold text-white">E-mail</strong>: Zadajte svoj e-mail – slúži ako prihlasovacie meno.</li>
                                <li><strong class="font-semibold text-white">Heslo</strong>: Vytvorte si heslo (aspoň 8&nbsp;znakov) a&nbsp;zopakujte ho.</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 2: Potvrdenie e-mailu</h3>
                            <p class="text-sm">Na zadanú adresu vám príde e-mail s&nbsp;odkazom. Kliknite naň a&nbsp;potvrďte tak svoj e-mail. Ak e-mail nevidíte, pozrite sa aj do spamu alebo si ho nechajte poslať znova.</p>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-neutral-100 mb-2">Krok 3: Schválenie manažérom</h3>
                            <p class="text-sm">Po potvrdení e-mailu vašu registráciu skontroluje manažér kina. Hneď ako ju schváli, príde vám e-mail a&nbsp;môžete sa prihlásiť a&nbsp;zapisovať sa na dni.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Zapisovanie na dni --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 2 ? null : 2)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-calendar-days text-brand-400"></i>
                            Zapisovanie na dni
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 2 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 2" style="display: none;">
                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800 bg-neutral-950 space-y-4 text-sm">
                        <p>Na deň sa zapíšete tlačidlom <strong class="font-semibold text-brand-400">„Zapísať sa“</strong> pri danom dni. Vaše meno sa potom zobrazí pri dni a&nbsp;tlačidlo sa zmení na <strong class="font-semibold text-brand-400">„Zapísaný“</strong>. Ak sa chcete odpísať, kliknite naň znova a&nbsp;odpísanie potvrďte.</p>
                        <p>Do poľa <strong class="font-semibold text-white">Poznámka</strong> môžete spresniť, kedy môžete (napr. <em>od 15:00</em> alebo <em>do 22:00</em>). Poznámka sa pridá ku dňom, na ktoré sa zapíšete odteraz – už existujúce zápisy nezmení.</p>
                        <p>Zapisovať sa môžete na viacero týždňov dopredu – koľko, určuje kino.</p>
                        <p><strong class="font-semibold text-white">Zamknutý týždeň</strong>: Keď manažér týždeň zamkne, pod výberom týždňa uvidíte „Týždeň je zamknutý – zápisy sa už nedajú meniť.“ Na dni v&nbsp;takom týždni sa už nemôžete zapísať ani z&nbsp;nich odpísať. Ak potrebujete niečo zmeniť, obráťte sa na manažéra.</p>
                    </div>
                </div>
            </div>

            {{-- 3. Absencie --}}
            <div>
                <h2>
                    <button
                        type="button"
                        @click="activeAccordion = (activeAccordion === 3 ? null : 3)"
                        class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                    >
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-umbrella-beach text-brand-400"></i>
                            Absencie
                        </span>
                        <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 3 }"></i>
                    </button>
                </h2>
                <div x-show="activeAccordion === 3" style="display: none;">
                    <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800 bg-neutral-950 space-y-4 text-sm">
                        <p>Ak v&nbsp;niektoré dni nemôžete pracovať, nahláste absenciu v&nbsp;sekcii <strong class="font-semibold text-white">Absencie</strong> tlačidlom „Pridať absenciu“. Vyberte rozsah dátumov, uveďte dôvod a&nbsp;kliknite na „Nahlásiť absenciu“. Na dni s&nbsp;nahlásenou absenciou sa nedá zapísať.</p>
                        <p>Absenciu treba nahlásiť vopred – aspoň toľko dní, koľko určí kino (nastavenie „Uzávierka absencií“). Na skoršie dni sa dohodnite s&nbsp;manažérom.</p>
                        <p>Ak sa vám plány zmenia, absenciu ukončite tlačidlom „Deaktivovať“. Neaktívnu absenciu môžete neskôr vymazať.</p>
                    </div>
                </div>
            </div>

            {{-- 4. Pre manažérov --}}
            @can('lock', \App\Models\Assignment::class)
                <div>
                    <h2>
                        <button
                            type="button"
                            @click="activeAccordion = (activeAccordion === 4 ? null : 4)"
                            class="group flex items-center justify-between w-full p-6 font-semibold text-neutral-100 hover:bg-neutral-800/60 focus:outline-none transition-colors duration-200"
                        >
                            <span class="flex items-center gap-3 text-lg">
                                <i class="fa-solid fa-shield-halved text-brand-400"></i>
                                Pre manažérov
                            </span>
                            <i class="fa-solid fa-chevron-down text-neutral-400 transition-transform duration-200" :class="{ 'rotate-180': activeAccordion === 4 }"></i>
                        </button>
                    </h2>
                    <div x-show="activeAccordion === 4" style="display: none;">
                        <div class="p-6 md:p-8 text-neutral-300 leading-relaxed border-t border-neutral-800 bg-neutral-950 space-y-4 text-sm">
                            <h3 class="text-lg font-bold text-neutral-100">Spravovanie používateľov</h3>
                            <ul class="list-disc list-inside space-y-1.5 text-neutral-300 pl-2">
                                <li><strong>Schvaľovanie registrácií</strong>: Keď nový používateľ potvrdí svoj e-mail, zobrazí sa v&nbsp;sekcii „Používatelia“ ako „Čaká na schválenie“. Po schválení mu príde e-mail a&nbsp;môže sa prihlásiť.</li>
                                <li><strong>Vytvorenie používateľa</strong>: Pošle e-mail s&nbsp;odkazom na nastavenie hesla (platí 24&nbsp;hodín).</li>
                                <li><strong>Správa členov</strong>: Úprava údajov a&nbsp;rolí, deaktivácia účtu.</li>
                            </ul>

                            <h3 class="text-lg font-bold text-neutral-100 pt-2">Spravovanie týždňov</h3>
                            <ul class="list-disc list-inside space-y-1.5 text-neutral-300 pl-2">
                                <li><strong>Zamknutie týždňa</strong>: Tlačidlo „Zamknúť týždeň“ (a&nbsp;„Odomknúť týždeň“) – zápisy sa už nedajú meniť. Brigádnici sa potom nemôžu zapísať ani odpísať, vy zápisy upraviť stále môžete (napr. niekoho odstrániť krížikom „×“ vedľa mena).</li>
                                <li><strong>Losovanie</strong>: Prepínač „Zobraziť voľných na losovanie“ ukáže pri každom dni zoznam „K&nbsp;dispozícii na losovanie“ – aktívnych brigádnikov, ktorí v&nbsp;ten deň nie sú zapísaní a&nbsp;nemajú absenciu.</li>
                                <li><strong>Excel</strong>: Stiahne týždeň – každý deň v&nbsp;samostatnom stĺpci so zapísanými („Priezvisko M. (poznámka)“) a&nbsp;na druhom hárku „Počet dní“ počet dní, na ktoré sa kto zapísal.</li>
                                <li><strong>Správa kina</strong>: V&nbsp;menu pod vaším menom – názov kina, začiatok týždňa, počet týždňov dopredu, uzávierka absencií a&nbsp;ako dlho sa uchováva neaktívna absencia.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>
</x-layout>

