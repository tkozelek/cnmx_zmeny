<x-layout>
    <div class="container mx-auto px-4 py-12 md:py-16">

        <div id="accordion-modern" data-accordion="open" class="w-5/6 mx-auto bg-slate-900/50 rounded-2xl ring-1 ring-white/10 divide-y divide-slate-700">

            <h2 id="accordion-modern-heading-1">
                <button type="button" class="group flex items-center justify-between w-full p-6 font-semibold rtl:text-right text-slate-100 hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-300" data-accordion-target="#accordion-modern-body-1" aria-expanded="false" aria-controls="accordion-modern-body-1">
                    <span class="flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-user-plus w-5 text-center text-indigo-400"></i>
                        Registrácia
                    </span>
                    <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-300 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-modern-body-1" class="hidden" aria-labelledby="accordion-modern-heading-1">
                <div class="p-6 md:p-8 text-slate-300 leading-relaxed">
                    <h2 class="text-2xl font-bold mb-5 text-slate-100">Krok 1: Vyplnenie registračného formulára</h2>
                    <p class="mb-5">Pre registráciu je potrebné vyplniť nasledujúce údaje:</p>
                    <ul class="list-disc list-inside mb-6 space-y-2">
                        <li><strong class="font-semibold text-slate-200">Meno</strong>: Zadajte svoje meno.</li>
                        <li><strong class="font-semibold text-slate-200">Priezvisko</strong>: Zadajte svoje priezvisko.</li>
                        <li><strong class="font-semibold text-slate-200">Email</strong>: Zadajte svoj email, ktorý bude slúžiť aj ako prihlasovacie meno.</li>
                        <li><strong class="font-semibold text-slate-200">Heslo</strong>: Vytvorte si heslo, ktoré bude chrániť váš účet.</li>
                    </ul>
                    <p class="mb-8">Po vyplnení všetkých údajov kliknite na tlačidlo <strong class="font-semibold text-indigo-300 uppercase">"Registrovať"</strong>.</p>

                    <h2 class="text-2xl font-bold mb-5 text-slate-100">Krok 2: Čakanie na schválenie</h2>
                    <p class="mb-8">Po úspešnom vyplnení registračného formulára bude vaša registrácia odoslaná na schválenie administrátorom. Tento proces môže chvíľu trvať, preto vás prosíme o trpezlivosť.</p>

                    <h2 class="text-2xl font-bold mb-5 text-slate-100">Krok 3: Potvrdenie registrácie</h2>
                    <p class="mb-5">Po schválení vašej registrácie administrátorom obdržíte informačný email, ktorý bude obsahovať potvrdenie, že vaša registrácia bola schválená.</p>

                    <h2 class="text-2xl font-bold mb-5 text-slate-100">Krok 4: Prihlásenie a začiatok používania</h2>
                    <p>Po obdržaní potvrdenia o schválení sa môžete prihlásiť na našu platformu pomocou vášho emailu a hesla, ktoré ste zadali pri registrácii. Teraz ste pripravení začať!</p>
                </div>
            </div>

            <h2 id="accordion-modern-heading-2">
                <button type="button" class="group flex items-center justify-between w-full p-6 font-semibold rtl:text-right text-slate-100 hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-300" data-accordion-target="#accordion-modern-body-2" aria-expanded="false" aria-controls="accordion-modern-body-2">
                    <span class="flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-calendar-days w-5 text-center text-indigo-400"></i>
                        Zapisovanie na zmeny
                    </span>
                    <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-300 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-modern-body-2" class="hidden" aria-labelledby="accordion-modern-heading-2">
                <div class="p-6 md:p-8 text-slate-300 leading-relaxed space-y-4">
                    <p>Zapisovanie na zmeny funguje pomocou stlačenia tlačidla <strong class="font-semibold text-indigo-300 uppercase">"Zapísať"</strong> (prípadne <strong class="font-semibold text-indigo-300 uppercase">"Odpísať"</strong>). Po stlačení tlačidla sa vaše meno objaví pod daným dňom.</p>
                    <p>Môžete tiež využiť textové pole <strong class="font-semibold text-slate-200">Extra Info</strong>, ktoré zabezpečuje dodatočné informácie o danom zápise (napr. ak môžete robiť od 15:00, resp. do 22:00 a podobne...).</p>
                    <p>Máte možnosť si vybrať zmeny až niekoľko týždňov dopredu, čo vám umožňuje lepšie plánovanie.</p>
                    <p>Ak vás nezaujíma, kto pracuje v daný deň, môžete si schovať mená zamestnancov. Táto funkcia vám spraví webovú aplikáciu prehľadnejšou.</p>
                </div>
            </div>

            <h2 id="accordion-modern-heading-3">
                <button type="button" class="group flex items-center justify-between w-full p-6 font-semibold rtl:text-right text-slate-100 hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-300" data-accordion-target="#accordion-modern-body-3" aria-expanded="false" aria-controls="accordion-modern-body-3">
                    <span class="flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-address-card w-5 text-center text-indigo-400"></i>
                        Profil
                    </span>
                    <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-300 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-modern-body-3" class="hidden" aria-labelledby="accordion-modern-heading-3">
                <div class="p-6 md:p-8 text-slate-300 leading-relaxed">
                    <p class="mb-8">Profil sa objaví po kliknutí na vaše meno v pravej časti navigácie. V profile máte k dispozícii nasledujúce možnosti:</p>
                    <h3 class="text-xl font-bold mb-4 text-slate-100">Pridanie absencie</h3>
                    <ul class="list-disc list-inside mb-8 space-y-2">
                        <li>Ak máte nejaký deň, kedy výhradne nechcete pracovať.</li>
                        <li>Možnosť pridania absencie na viac ako jeden deň.</li>
                        <li>Po týždni od dátumu skončenia platnosti absencie, je možné ju vymazať.</li>
                    </ul>
                    <h3 class="text-xl font-bold mb-4 text-slate-100">Nastavenia</h3>
                    <p>V sekcii nastavení je aktuálne možnosť zmeny hesla.</p>
                </div>
            </div>

            <h2 id="accordion-modern-heading-5">
                <button type="button" class="group flex items-center justify-between w-full p-6 font-semibold rtl:text-right text-slate-100 hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-300" data-accordion-target="#accordion-modern-body-5" aria-expanded="false" aria-controls="accordion-modern-body-5">
                    <span class="flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-wrench w-5 text-center text-indigo-400"></i>
                        Nahlásenie chyby
                    </span>
                    <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-300 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-modern-body-5" class="hidden" aria-labelledby="accordion-modern-heading-5">
                <div class="p-6 md:p-8 text-slate-300 leading-relaxed">
                    <p class="mb-5">Ak narazíte na chybu, kliknite na váš profil a následne na "Nahlásiť chybu". Objaví sa formulár, kde je potrebné vyplniť nasledujúce údaje:</p>
                    <ul class="list-disc list-inside mb-6 space-y-2">
                        <li><strong class="font-semibold text-slate-200">Predmet</strong>: Uveďte krátky a výstižný predmet chyby.</li>
                        <li><strong class="font-semibold text-slate-200">Kde sa to stalo</strong>: Špecifikujte časť stránky, kde sa chyba objavila.</li>
                        <li><strong class="font-semibold text-slate-200">Popis chyby</strong>: Popíšte, ako chyba nastala, čo ste robili, keď sa objavila.</li>
                        <li><strong class="font-semibold text-slate-200">Priložte obrázok chyby (voliteľné)</strong>: Môžete priložiť snímku obrazovky.</li>
                    </ul>
                    <p class="mb-5">Po vyplnení formulára kliknite na tlačidlo "Odoslať".</p>
                    <p>Ďakujeme za vašu spoluprácu pri zlepšovaní našej platformy!</p>
                </div>
            </div>

            @if(auth()->user() && auth()->user()->hasRole(config('constants.roles.admin')))
                <h2 id="accordion-modern-heading-4">
                    <button type="button" class="group flex items-center justify-between w-full p-6 font-semibold rtl:text-right text-slate-100 hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-300" data-accordion-target="#accordion-modern-body-4" aria-expanded="false" aria-controls="accordion-modern-body-4">
                        <span class="flex items-center gap-3 text-lg">
                            <i class="fa-solid fa-book-bible w-5 text-center text-indigo-400"></i>
                            Administrátor
                        </span>
                        <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-300 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </button>
                </h2>
                <div id="accordion-modern-body-4" class="hidden" aria-labelledby="accordion-modern-heading-4">
                    <div class="p-6 md:p-8 text-slate-300 leading-relaxed">
                        <p class="mb-8">Ako administrátor máte na celej stránke viac možností ako klasický používateľ.</p>
                        <h3 class="text-xl font-bold mb-4 text-slate-100">Spravovanie používateľov</h3>
                        <ul class="list-disc list-inside mb-8 space-y-2">
                            <li><strong>Vytvorenie nového používateľa</strong>: Po vyplnení údajov sa mu odošle email na reset hesla.</li>
                            <li><strong>Spravovanie aktuálnych používateľov</strong>: Môžete meniť základné údaje (email, meno) a ich <strong>ROLU</strong>.</li>
                            <li><strong>Počet novo registrovaných</strong>: Vedľa profilu sa zobrazuje počet používateľov čakajúcich na overenie.</li>
                        </ul>

                        <h3 class="text-xl font-bold mb-4 text-slate-100">Zapisovanie na zmeny</h3>
                        <ul class="list-disc list-inside mb-6 space-y-2">
                            <li><strong>Zamkni</strong>: Zamkne zvolený týždeň a vygeneruje nové týždne, ak je to potrebné.</li>
                            <li><strong>Excel</strong>: Vytvorí excel export daného týždňa s rozpisom ľudí a počtami zmien.</li>
                            <li><strong>Súbory</strong>: Možnosť nahrať, stiahnuť, zviditeľniť alebo zmazať súbory pre brigádnikov.</li>
                        </ul>
                        <p class="mb-8">V dolnej časti vidíte <strong>počet zapísaných ľudí a aktívne absencie</strong> pre daný týždeň.</p>

                        <h3 class="text-xl font-bold mb-4 text-slate-100">Absencie</h3>
                        <p>V sekcii absencie vo vašom profile vidíte aktívne aj vypršané absencie všetkých používateľov.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout>
