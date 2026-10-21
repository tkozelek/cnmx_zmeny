@php
    $operator = config('privacy.operator');
    $logDays = config('logging.channels.daily.days');
    $sessionMinutes = config('session.lifetime');
@endphp

<x-layout title="Ochrana osobných údajov" description="Aké údaje aplikácia Cinemax Zmeny spracúva, načo, kto ich vidí, ako dlho a aké máš práva.">
    <div class="container mx-auto px-4 py-12 md:py-16">
        <article class="mx-auto w-full max-w-3xl space-y-10 text-neutral-300 leading-relaxed">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold tracking-tight text-white">Ochrana osobných údajov</h1>
                <p class="text-neutral-400">Čo o tebe aplikácia vie, načo to používam, kto to vidí, ako dlho to uchovávam a aké máš práva.</p>
            </header>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Kto tvoje údaje spracúva</h2>
                <p>
                    Aplikáciu Cinemax Zmeny som vytvoril a prevádzkujem ja, <strong class="font-semibold text-white">{{ $operator['name'] }}</strong>,
                    ako súkromná osoba vo voľnom čase, aby sa zamestnanci kina mohli jednoduchšie zapisovať na zmeny.
                    Nie je to aplikácia spoločnosti Cinemax ani tvojho zamestnávateľa – za údaje v nej zodpovedám ja.
                </p>
                <p>S otázkami k osobným údajom aj so žiadosťami o uplatnenie práv mi napíš na <a href="mailto:{{ $operator['email'] }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ $operator['email'] }}</a>.</p>
                <p>Zodpovednú osobu (DPO) som neurčil, pretože mi to zákon neukladá.</p>
                <p class="text-sm text-neutral-400">Tieto informácie poskytujem podľa čl. 13 nariadenia (EÚ) 2016/679 o ochrane fyzických osôb pri spracúvaní osobných údajov (ďalej „GDPR“) a zákona č. 18/2018 Z. z. o ochrane osobných údajov.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Tvoj účet</h2>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Meno, priezvisko, e-mail, heslo (uložené len v podobe, z ktorej sa heslo nedá spätne zistiť), kino, ktoré si vyberieš, tvoja rola v ňom, kedy účet vznikol, kedy ťa kino schválilo, čas posledného prihlásenia a či je účet zablokovaný.</dd></div>
                    <div><dt class="font-semibold text-white">Načo</dt><dd>Aby sa dalo prihlásiť, aby ťa manažér kina mohol schváliť a aby kino vedelo, kto sa na ktoré dni zapísal.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Svoj profil vidíš ty a manažéri tvojho kina – tí vidia aj tvoj e-mail, rolu, kedy účet vznikol, čas posledného prihlásenia, štatistiku tvojich zápisov a tvoje absencie. Kolegovia z kina vidia pri dňoch, na ktoré máš zápis, tvoje priezvisko so začiatočným písmenom mena.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, o ktoré registráciou žiadaš (čl. 6 ods. 1 písm. b) GDPR).</dd></div>
                    <div><dt class="font-semibold text-white">Ako dlho</dt><dd>Registrácia, ktorú žiadne kino neschváli, sa automaticky vymaže 6 mesiacov po vytvorení. Účet, z ktorého sa 12 mesiacov nikto neprihlásil a ktorý nemá zápis za posledných 12 mesiacov ani do budúcna, sa automaticky vymaže aj so zápismi a absenciami. Účty manažérov kina sa automaticky nemažú. O vymazanie účtu ma môžeš kedykoľvek požiadať.</dd></div>
                    <div><dt class="font-semibold text-white">Musíš ich uviesť?</dt><dd>Meno, priezvisko, e-mail, heslo a kino sú povinné – bez nich účet vytvoriť nejde.</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Zápisy na dni a absencie</h2>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Dni, na ktoré sa zapíšeš, a poznámka k zápisu (napríklad „od 17:00“). Pri absencii jej termín, dôvod a či ešte platí. Do dôvodu prosím nepíš zdravotné dôvody ani diagnózy – pri chorobe stačí „osobné dôvody“.</dd></div>
                    <div><dt class="font-semibold text-white">Načo</dt><dd>Aby sa dal naplánovať týždeň a manažér vedel, kedy nemôžeš pracovať.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Zápisy aj ich poznámky vidia kolegovia z tvojho kina. Absencie a ich dôvod vidíš len ty a manažéri tvojho kina.</dd></div>
                    <div><dt class="font-semibold text-white">Rozpis v tabuľke</dt><dd>Manažér si môže týždeň stiahnuť ako tabuľku Excel: priezvisko so začiatočným písmenom mena, poznámka k zápisu a počet dní na človeka. S touto kópiou ďalej narába tvoje kino ako zamestnávateľ.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, ktorú používaš (čl. 6 ods. 1 písm. b) GDPR).</dd></div>
                    <div><dt class="font-semibold text-white">Ako dlho</dt><dd>Kým existuje tvoj účet (pozri vyššie). Absenciu môžeš zmazať aj ty, podľa pravidiel kina.</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Rozpis zmien (len v niektorých kinách)</h2>
                <p>Kino si môže zapnúť rozpis zmien. Ak ho tvoje kino nemá zapnutý, nič z tejto časti sa ťa netýka.</p>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Na ktorej pozícii (napríklad bufet, pokladňa) a od kedy v daný deň pracuješ a kto ťa na ňu zaradil. Z tvojich zápisov sa počíta, koľko a aké náročné dni si odpracoval za posledné týždne – manažérovi to slúži ako odporúčanie, koho zaradiť.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Zverejnený rozpis vidia kolegovia z tvojho kina. Rozpracovaný rozpis a poradie podľa odpracovaných dní vidia len manažéri kina.</dd></div>
                    <div><dt class="font-semibold text-white">História zmien</dt><dd>Aplikácia si zaznamenáva, kto a kedy v rozpise koho zaradil, presunul alebo odobral. Poznámky k zápisom sa do histórie nezapisujú. Históriu vidia manažéri kina a uchovávam ju 12 mesiacov.</dd></div>
                    <div><dt class="font-semibold text-white">AI návrh</dt><dd>Ak má kino zapnutý aj AI návrh rozpisu, manažér si môže nechať navrhnúť zaradenia od služby Google Gemini. Služba nedostane tvoje meno, e-mail ani iný údaj, podľa ktorého by ťa spoznala – len náhodný kód platný pre jeden návrh, počet a náročnosť tvojich odpracovaných dní a zoznam voľných pozícií. Návrh nikoho nezaradí sám, rozhoduje manažér.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, ktorú používaš (čl. 6 ods. 1 písm. b) GDPR), pri histórii zmien môj oprávnený záujem na tom, aby sa dalo dohľadať, kto rozpis zmenil (čl. 6 ods. 1 písm. f) GDPR).</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Ak máš účet z pôvodnej verzie</h2>
                <p>Tvoj účet, zápisy a absencie som do tejto verzie preniesol z pôvodnej aplikácie, ktorú som tiež prevádzkoval ja. Heslo ostalo rovnaké. Evidenciu odpracovaných hodín a nahrané súbory som nepreniesol.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">E-maily</h2>
                <p>Aplikácia ti posiela len e-maily k účtu: overenie e-mailovej adresy, odkaz na obnovu hesla, pozvánku, ak ti účet vytvorí manažér, a oznámenie, že ťa kino schválilo. Ak má tvoje kino zapnutý rozpis zmien a si v ňom zapísaný, dostaneš aj oznámenie, keď sa rozpis zverejní alebo zmení. Nič iné ti neposielam.</p>
                <p>Ak mi napíšeš na {{ $operator['email'] }}, tvoja správa je v mojej schránke Gmail, ktorú prevádzkuje Google. Správy so žiadosťami k osobným údajom si nechám najviac 12 mesiacov po vybavení.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Bezpečnosť a prevádzka</h2>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>Na ochranu prihlásenia a formulárov pred zneužitím si aplikácia najviac hodinu pamätá počet pokusov z tvojej IP adresy a pre zadaný e-mail – uložené len ako odtlačok (hash), nie v čitateľnej podobe. Potom tieto záznamy vymaže, najneskôr nasledujúci deň.</li>
                    <li>Záznamy o tom, ktorý účet (podľa čísla) zmenil alebo vymazal ktorý záznam a ktoré polia, a záznamy o chybách uchovávam {{ $logDays }} dní. Mená, e-maily ani dôvody absencií sa do záznamov o zmenách nezapisujú. Záznam o chybe môže výnimočne obsahovať údaje z požiadavky, pri ktorej chyba vznikla.</li>
                    <li>Ako prevádzkovateľ spravujem všetky kiná cez správu platformy. Čo v nej zmením, sa zaznamená (kto, kedy, v ktorom kine, ktorý záznam a ktoré polia – bez mien, e-mailov a dôvodov absencií) a tieto záznamy uchovávam 12 mesiacov.</li>
                    <li>Aby som videl, či aplikácia funguje a ktoré stránky sú pomalé, 7 dní uchovávam počet požiadaviek podľa účtu (jeho čísla), adresy pomalých stránok a technické záznamy o chybách a výkone. Vidím ich len ja.</li>
                    <li>Poskytovateľ webhostingu môže na zabezpečenie servera zaznamenávať technické údaje o prístupoch (IP adresu, čas, adresu stránky).</li>
                </ul>
                <p>Právnym základom je môj oprávnený záujem na bezpečnej a fungujúcej aplikácii (čl. 6 ods. 1 písm. f) GDPR).</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Cookies a úložisko prehliadača</h2>
                <p>Aplikácia používa len tieto technicky nevyhnutné cookies, ktoré podľa § 109 ods. 8 zákona č. 452/2021 Z. z. o elektronických komunikáciách nevyžadujú súhlas:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li><strong class="font-semibold text-white">{{ config('session.cookie') }}</strong> – drží tvoje prihlásenie. Platí {{ $sessionMinutes }} minút od poslednej aktivity, potom sa relácia na serveri do hodiny vymaže.</li>
                    <li><strong class="font-semibold text-white">XSRF-TOKEN</strong> – chráni formuláre pred podvrhnutím z iných stránok. Platí {{ $sessionMinutes }} minút od poslednej aktivity.</li>
                    <li><strong class="font-semibold text-white">remember_web_…</strong> – len ak pri prihlásení zaškrtneš „Zapamätať si ma“. Drží prihlásenie približne 400 dní alebo do odhlásenia.</li>
                </ul>
                <p>V úložisku prehliadača si aplikácia pamätá, či máš v kalendári zapnuté zobrazenie mien a losovania (položky „names_checkbox“ a „draw_checkbox“), a po aktualizácii aplikácie jednu technickú značku, aby sa stránka obnovila len raz. Tieto údaje sa nikam neposielajú.</p>
                <p>Nepoužívam analytické ani reklamné cookies. Písma a ikony sú uložené priamo na serveri aplikácie a stránky nenačítavajú nič zo serverov iných firiem – prehliadaču to zakazujú aj bezpečnostné hlavičky aplikácie.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Komu údaje poskytujem</h2>
                <p>Údaje nikomu nepredávam. Spracúvajú ich pre mňa len títo sprostredkovatelia, a to len v rozsahu, ktorý potrebujú:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>poskytovateľ webhostingu{{ config('privacy.hosting') ? ' – '.config('privacy.hosting') : '' }}, na ktorého serveri beží aplikácia aj jej databáza,</li>
                    <li>poskytovateľ odosielania e-mailov{{ config('privacy.mail_provider') ? ' – '.config('privacy.mail_provider') : '' }},</li>
                    <li>Google (služba Gemini) – len v kinách so zapnutým AI návrhom rozpisu a len bez mien a kontaktov, ako je opísané vyššie.</li>
                </ul>
                <p>Manažéri tvojho kina vidia v aplikácii to, čo je opísané vyššie. Štátnym orgánom poskytnem údaje len vtedy, keď mi to ukladá zákon.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Prenos mimo Európskej únie</h2>
                <p>
                    Aplikácia a jej databáza bežia na serveri v Slovenskej republike.
                    @if(config('privacy.mail_in_eu'))
                        Aj e-maily z aplikácie sa odosielajú v rámci Európskej únie.
                    @endif
                    Mimo Európskej únie sa môžu dostať správy, ktoré mi pošleš do schránky Gmail, a údaje bez mien pre AI návrh rozpisu (služba Gemini): Google ich môže spracúvať aj v USA. Spoločnosť Google LLC je zapojená do rámca ochrany údajov medzi EÚ a USA, pre ktorý Európska komisia rozhodla o primeranej úrovni ochrany (vykonávacie rozhodnutie (EÚ) 2023/1795).
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Automatizované rozhodovanie</h2>
                <p>O nikom nerozhoduje automat. Registrácie schvaľuje manažér kina. Poradie podľa odpracovaných dní a AI návrh v rozpise zmien sú len odporúčania – kto bude kde pracovať, rozhoduje manažér. Automaticky sa len mažú staré údaje podľa lehôt na tejto stránke.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Tvoje práva</h2>
                <p>Máš právo:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>na prístup k svojim údajom a ich kópiu (čl. 15 GDPR),</li>
                    <li>na opravu nesprávnych údajov (čl. 16 GDPR) – meno ti opraví aj manažér kina, e-mail ja,</li>
                    <li>na vymazanie (čl. 17 GDPR) – napríklad celého účtu,</li>
                    <li>na obmedzenie spracúvania (čl. 18 GDPR),</li>
                    <li>na prenosnosť svojich údajov z aplikácie – pošlem ti ich v bežnom strojovo čitateľnom formáte (čl. 20 GDPR).</li>
                </ul>
                <p>Stačí mi napísať. Odpoviem bezplatne a najneskôr do jedného mesiaca. Ak si nebudem istý, že žiadosť posielaš ty, môžem ťa požiadať o overenie – napríklad odpoveďou z e-mailu, ktorý máš v aplikácii.</p>
            </section>

            <section class="space-y-3 rounded-lg border border-neutral-700 bg-neutral-900 p-5">
                <h2 class="text-xl font-semibold text-white">Právo namietať</h2>
                <p>Ak údaje spracúvam na základe oprávneného záujmu (ochrana prihlásenia a formulárov pred zneužitím, záznamy o chybách a zmenách, sledovanie pomalých stránok), môžeš kedykoľvek namietať z dôvodov týkajúcich sa tvojej konkrétnej situácie (čl. 21 GDPR). Spracúvanie potom ukončím, ibaže preukážem nevyhnutné oprávnené dôvody, ktoré prevažujú nad tvojimi záujmami, právami a slobodami.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Sťažnosť</h2>
                <p>Ak si myslíš, že s tvojimi údajmi nezaobchádzam správne, môžeš podať sťažnosť (návrh na začatie konania podľa čl. 77 GDPR) na Úrad na ochranu osobných údajov Slovenskej republiky, Galvaniho 7/B, 821 04 Bratislava, <a href="https://dataprotection.gov.sk" class="text-brand-400 hover:text-brand-300" rel="noopener">dataprotection.gov.sk</a>, statny.dozor@pdp.gov.sk.</p>
            </section>

            <footer class="border-t border-neutral-800 pt-6 text-sm text-neutral-400">
                <p>Ak zmením spôsob, akým s údajmi zaobchádzam, upravím aj túto stránku.</p>
                <p class="mt-1">Platné od 21.10.2026</p>
            </footer>
        </article>
    </div>
</x-layout>
