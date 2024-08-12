<x-layout>
    <div class="container mx-auto my-5">
        <div id="accordion-open" data-accordion="open">
            <h2 id="accordion-open-heading-1">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right border border-b-0 rounded-t-xl focus:ring-1 focus:ring-gray-800 border-gray-700 bg-slate-800 hover:bg-gray-800 gap-3 !text-gray-200" data-accordion-target="#accordion-open-body-1" aria-expanded="false" aria-controls="accordion-open-body-1">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-user-plus"></i> Registrácia</span>
                    <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-open-body-1" class="hidden" aria-labelledby="accordion-open-heading-1">
                <div class="p-5 border border-b-0 border-gray-700 bg-gray-900 text-white">
                    <h2 class="text-2xl font-semibold mb-4">Krok 1: Vyplnenie registračného formulára</h2>
                    <p class="mb-4">Pre registráciu je potrebné vyplniť nasledujúce údaje:</p>
                    <ul class="list-disc list-inside mb-4">
                        <li><strong>Meno</strong>: Zadajte svoje meno.</li>
                        <li><strong>Priezvisko</strong>: Zadajte svoje priezvisko.</li>
                        <li><strong>Email</strong>: Zadajte svoj email, ktorý bude slúžiť aj ako prihlasovacie meno.</li>
                        <li><strong>Heslo</strong>: Vytvorte si heslo, ktoré bude chrániť váš účet.</li>
                    </ul>
                    <p class="mb-6">Po vyplnení všetkých údajov kliknite na tlačidlo <strong>"Registrovať"</strong>.</p>

                    <h2 class="text-2xl font-semibold mb-4">Krok 2: Čakanie na schválenie</h2>
                    <p class="mb-6">Po úspešnom vyplnení registračného formulára bude vaša registrácia odoslaná na schválenie administrátorom. Tento proces môže chvíľu trvať, preto vás prosíme o trpezlivosť.</p>

                    <h2 class="text-2xl font-semibold mb-4">Krok 3: Potvrdenie registrácie</h2>
                    <p class="mb-4">Po schválení vašej registrácie administrátorom obdržíte informačný email. Tento email bude obsahovať:</p>
                    <ul class="list-disc list-inside mb-4">
                        <li>Potvrdenie, že vaša registrácia bola schválená.</li>
                    </ul>

                    <h2 class="text-2xl font-semibold mb-4">Krok 4: Prihlásenie a začiatok používania</h2>
                    <p class="mb-6">Po obdržaní potvrdenia o schválení sa môžete prihlásiť na našu platformu pomocou vášho emailu a hesla, ktoré ste zadali pri registrácii.</p>
                    <p class="mb-6">Teraz ste pripravení začať používať našu platformu a využívať všetky jej funkcie!</p>
                </div>
            </div>
            <h2 id="accordion-open-heading-2">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right border border-b-0 focus:ring-1 focus:ring-gray-800 border-gray-700 bg-slate-800 hover:bg-gray-800 gap-3 !text-gray-200" data-accordion-target="#accordion-open-body-2" aria-expanded="false" aria-controls="accordion-open-body-2">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-calendar-days"></i>Zapisovanie na zmeny</span>
                    <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-open-body-2" class="hidden" aria-labelledby="accordion-open-heading-2">
                <div class="p-5 border border-b-0 border-gray-700 bg-gray-900 text-white">
                    <h2 class="text-2xl font-semibold mb-4">Zapisovanie na zmeny</h2>
                    <p class="mb-4">Zapisovanie na zmeny funguje pomocou stlačenia tlačidla <strong class="uppercase">"Zapísať"</strong> (prípadne <strong class="uppercase">"Odpísať"</strong>). Po stlačení tlačidla sa vaše meno objaví pod daným dňom.</p>

                    <p class="mb-4">Môžete tiež využiť textové pole extra info, ktoré zabezpečuje dodatočné informácie o danom zápise (napr. ak môžete robiť od 15:00, resp. do 22:00 a podobne...).</p>

                    <p class="mb-4">Máte možnosť si vybrať zmeny až niekoľko týždňov dopredu, čo vám umožňuje lepšie plánovanie.</p>

                    <p class="mb-4">Ak vás nezaujíma, kto pracuje v daný deň, môžete si schovať mená zamestnancov. Táto funkcia vám spraví webovú aplikáciu prehľadnejšou.</p>
                </div>
            </div>

            <h2 id="accordion-open-heading-3">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right border border-b-0 focus:ring-1 focus:ring-gray-800 border-gray-700 bg-slate-800 hover:bg-gray-800 gap-3 !text-gray-200" data-accordion-target="#accordion-open-body-3" aria-expanded="false" aria-controls="accordion-open-body-3">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-address-card"></i>Profil</span>
                    <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-open-body-3" class="hidden" aria-labelledby="accordion-open-heading-3">
                <div class="p-5 border border-b-0 border-gray-700 bg-gray-900 text-white">
                    <h2 class="text-2xl font-semibold mb-4">Profil</h2>
                    <p class="mb-4">Profil sa objaví po kliknutí na vaše meno v pravej časti navigácie (na vrchu stránky). V profile máte k dispozícii nasledujúce možnosti:</p>

                    <h3 class="text-xl font-semibold mb-2">Pridanie absencie</h3>
                    <ul class="list-disc list-inside mb-4">
                        <li>Ak máte nejaký deň, kedy výhradne nechcete pracovať.</li>
                        <li>Možnosť pridania absencie na viac ako jeden deň.</li>
                        <li>Po týždni od dátumu skončenia platnosti absencie, je možné ju vymazať.</li>
                    </ul>


                    <h3 class="text-xl font-semibold mb-2">Nastavenia</h3>
                    <p class="mb-4">V sekcii nastavení je aktuálne možnosť zmeny hesla.</p>
                </div>
            </div>

            <h2 id="accordion-open-heading-5">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right border border-b-0 focus:ring-1 focus:ring-gray-800 border-gray-700 bg-slate-800 hover:bg-gray-800 gap-3 !text-gray-200" data-accordion-target="#accordion-open-body-5" aria-expanded="false" aria-controls="accordion-open-body-5">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-wrench"></i>Nahlásenie chyby</span>
                    <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-open-body-5" class="hidden" aria-labelledby="accordion-open-heading-5">
                <div class="p-5 border border-b-0 border-gray-700 bg-gray-900 text-white">
                    <h2 class="text-2xl font-semibold mb-4">Nahlásenie chyby</h2>
                    <p class="mb-4">Ak narazíte na chybu, môžete ju nahlásiť nasledujúcim spôsobom:</p>

                    <p class="mb-4">Kliknite na váš profil v pravej časti navigácie (na vrchu stránky) a následne na "Nahlásiť chybu". Objaví sa formulár, kde je potrebné vyplniť nasledujúce údaje:</p>

                    <ul class="list-disc list-inside mb-4">
                        <li><strong>Predmet</strong>: Uveďte krátky a výstižný predmet chyby.</li>
                        <li><strong>Kde sa to stalo</strong>: Špecifikujte časť stránky, kde sa chyba objavila.</li>
                        <li><strong>Popis chyby</strong>: Popíšte, ako chyba nastala, čo ste robili, keď sa objavila.</li>
                        <li><strong>Priložte obrázok chyby (nie je povinné)</strong>: Môžete priložiť snímku obrazovky s chybou pre lepšie pochopenie problému.</li>
                    </ul>

                    <p class="mb-4">Po vyplnení formulára kliknite na tlačidlo "Odoslať".</p>

                    <p>Ďakujeme za vašu spoluprácu pri zlepšovaní našej platformy!</p>
                </div>
            </div>

            @if(auth()->user() && auth()->user()->hasRole(config('constants.roles.admin')))
                <h2 id="accordion-open-heading-4">
                    <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right border border-b-0 focus:ring-1 focus:ring-gray-800 border-gray-700 bg-slate-800 hover:bg-gray-800 gap-3 !text-gray-200" data-accordion-target="#accordion-open-body-4" aria-expanded="false" aria-controls="accordion-open-body-4">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-book-bible"></i>Administrátor</span>
                        <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </button>
                </h2>
                <div id="accordion-open-body-4" class="hidden" aria-labelledby="accordion-open-heading-4">
                    <div class="p-5 border border-b-0 border-gray-700 bg-gray-900 text-white">
                        <h2 class="text-2xl font-semibold mb-4">Administrátor</h2>
                        <p class="mb-4">Ako administrátor máte na celej stránke viac možností ako klasický používateľ.</p>

                        <h3 class="text-xl font-semibold mb-2">Spravovanie používateľov</h3>
                        <ul class="list-disc list-inside mb-4">
                            <li><strong>Vytvorenie nového používateľa</strong>: Po vyplnení údajov a stlačení tlačidla sa mu odošle email na reset hesla. Po resetovaní môže používať účet.</li>
                            <li><strong>Spravovanie aktuálnych používateľov</strong>: Môžete meniť základné údaje ako email, meno, priezvisko, alebo ich <strong>ROLU</strong>.</li>
                            <li><strong>Počet novo registrovaných používateľov</strong>: Vedľa profilu sa vám zobrazuje počet novo registrovaných používateľov (neoverených), ktorí čakajú na overenie. Po overení sa im odošle email.</li>
                        </ul>

                        <h3 class="text-xl font-semibold mb-2">Zapisovanie na zmeny</h3>
                        <ul class="list-disc list-inside mb-4">
                            <li><strong>Zamkni</strong>: Zamkne zvolený týždeň. Pri zamykaní sa kontroluje, či je vygenerovaný dostatočný počet týždňov dopredu. Ak nie, vygenerujú sa.</li>
                            <li><strong>Excel</strong>: Vytvorí excel export daného týždňa s dňami a ľuďmi, ktorí sú tam zapísaní. Na ďalšom hárku sú počty zapísaných dní pre danú osobu v danom týždni.</li>
                            <li><strong>Súbory</strong>: Možnosť nahrať súbor, stiahnuť ho, zviditeľniť pre brigádnikov alebo zmazať.</li>
                        </ul>
                        <p class="mb-4">Dole vidíte <strong>počet zapísaných ľudí v danom týždni a aktívne absencie</strong>, ktoré zasahujú do daného týždňa.</p>

                        <h3 class="text-xl font-semibold mb-2">Absencie</h3>
                        <p class="mb-4">V sekcii absencie vo vašom profile vidíte aktívne aj vypršané absencie.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout>
