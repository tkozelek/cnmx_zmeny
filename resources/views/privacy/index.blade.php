<x-layout title="Ochrana osobných údajov" description="Aké údaje aplikácia Cinemax Zmeny spracúva, načo, kto ich vidí, ako dlho a aké máš práva.">
    <div class="container mx-auto px-4 py-12 md:py-16">
        <article class="mx-auto w-full max-w-3xl space-y-10 text-neutral-300 leading-relaxed">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold tracking-tight text-white">Ochrana osobných údajov</h1>
                <p class="text-neutral-400">Čo o&nbsp;tebe aplikácia vie, načo to používam, kto to vidí, ako dlho to uchovávam a&nbsp;aké máš práva.</p>
            </header>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Kto tvoje údaje spracúva</h2>
                <p>
                    Aplikáciu Cinemax Zmeny som vytvoril a&nbsp;prevádzkujem ja, <strong class="font-semibold text-white">{{ $operator['name'] }}</strong>,
                    ako súkromná osoba vo voľnom čase, aby sa brigádnici kina mohli jednoduchšie zapisovať na zmeny.
                    Nie je to aplikácia spoločnosti Cinemax ani tvojho zamestnávateľa – za údaje v&nbsp;nej zodpovedám ja.
                </p>
                <p>S&nbsp;otázkami k&nbsp;osobným údajom aj so žiadosťami o&nbsp;uplatnenie práv mi napíš na <a href="mailto:{{ $operator['email'] }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ $operator['email'] }}</a>.</p>
                <p>Zodpovednú osobu (DPO) som neurčil, pretože mi to zákon neukladá.</p>
                <p class="text-sm text-neutral-400">Tieto informácie poskytujem podľa čl. 13 nariadenia (EÚ) 2016/679 o&nbsp;ochrane fyzických osôb pri spracúvaní osobných údajov (ďalej „GDPR“) a&nbsp;zákona č. 18/2018 Z. z. o&nbsp;ochrane osobných údajov.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Tvoj účet</h2>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Meno, priezvisko, e-mail, heslo (uložené len v&nbsp;podobe, z&nbsp;ktorej sa heslo nedá spätne zistiť), kino, ktoré si vyberieš, tvoja rola v&nbsp;ňom, kedy účet vznikol, kedy ťa kino schválilo, čas posledného prihlásenia a&nbsp;či je účet zablokovaný.</dd></div>
                    <div><dt class="font-semibold text-white">Načo</dt><dd>Aby sa dalo prihlásiť, aby ťa manažér kina mohol schváliť a&nbsp;aby kino vedelo, kto sa na ktoré dni zapísal.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Svoj profil vidíš ty a&nbsp;manažéri tvojho kina – tí vidia aj tvoj e-mail, rolu, kedy účet vznikol, čas posledného prihlásenia, štatistiku tvojich zápisov a&nbsp;tvoje absencie. Kolegovia z&nbsp;kina vidia pri dňoch, na ktoré máš zápis, tvoje priezvisko so začiatočným písmenom mena.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, o&nbsp;ktoré registráciou žiadaš (čl. 6 ods. 1 písm. b) GDPR).</dd></div>
                    <div><dt class="font-semibold text-white">Ako dlho</dt><dd>Registrácia, ktorú žiadne kino neschváli, sa automaticky vymaže 6 mesiacov po vytvorení. Účet, z&nbsp;ktorého sa 12 mesiacov nikto neprihlásil a&nbsp;ktorý nemá zápis za posledných 12 mesiacov ani do budúcna, sa automaticky vymaže aj so zápismi a&nbsp;absenciami. Účty manažérov kina sa automaticky nemažú. O&nbsp;vymazanie účtu ma môžeš kedykoľvek požiadať.</dd></div>
                    <div><dt class="font-semibold text-white">Musíš ich uviesť?</dt><dd>Meno, priezvisko, e-mail, heslo a&nbsp;kino sú povinné – bez nich účet vytvoriť nejde.</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Zápisy na dni a&nbsp;absencie</h2>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Dni, na ktoré sa zapíšeš, a&nbsp;poznámka k&nbsp;zápisu (napríklad „od 17:00“). Pri absencii jej termín, dôvod a&nbsp;či ešte platí. Do dôvodu prosím nepíš zdravotné dôvody ani diagnózy – pri chorobe stačí „osobné dôvody“.</dd></div>
                    <div><dt class="font-semibold text-white">Načo</dt><dd>Aby sa dal naplánovať týždeň a&nbsp;manažér vedel, kedy nemôžeš pracovať.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Zápisy aj ich poznámky vidia kolegovia z&nbsp;tvojho kina. Absencie a&nbsp;ich dôvod vidíš len ty a&nbsp;manažéri tvojho kina.</dd></div>
                    <div><dt class="font-semibold text-white">Rozpis v&nbsp;tabuľke</dt><dd>Manažér si môže týždeň stiahnuť ako tabuľku Excel: priezvisko so začiatočným písmenom mena, poznámka k&nbsp;zápisu a&nbsp;počet dní na človeka. S&nbsp;touto kópiou ďalej narába tvoje kino ako zamestnávateľ.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, ktorú používaš (čl. 6 ods. 1 písm. b) GDPR).</dd></div>
                    <div><dt class="font-semibold text-white">Ako dlho</dt><dd>Kým existuje tvoj účet (pozri vyššie). Absenciu môžeš zmazať aj ty, podľa pravidiel kina.</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Rozpis zmien (len v&nbsp;niektorých kinách)</h2>
                <p>Kino si môže zapnúť rozpis zmien. Ak ho tvoje kino nemá zapnutý, nič z&nbsp;tejto časti sa ťa netýka.</p>
                <dl class="space-y-3">
                    <div><dt class="font-semibold text-white">Aké údaje</dt><dd>Na ktorej pozícii (napríklad bufet, pokladňa) a&nbsp;od kedy v&nbsp;daný deň pracuješ a&nbsp;kto ťa na ňu zaradil. Z&nbsp;tvojich zápisov sa počíta, koľko a&nbsp;aké náročné dni si odpracoval za posledné týždne – manažérovi to slúži ako odporúčanie, koho zaradiť.</dd></div>
                    <div><dt class="font-semibold text-white">Kto ich vidí</dt><dd>Zverejnený rozpis vidia kolegovia z&nbsp;tvojho kina. Rozpracovaný rozpis a&nbsp;poradie podľa odpracovaných dní vidia len manažéri kina.</dd></div>
                    <div><dt class="font-semibold text-white">História zmien</dt><dd>Aplikácia si zaznamenáva, kto a&nbsp;kedy v&nbsp;rozpise koho zaradil, presunul alebo odobral. Poznámky k&nbsp;zápisom sa do histórie nezapisujú. Históriu vidia manažéri kina a&nbsp;uchovávam ju 12 mesiacov.</dd></div>
                    <div><dt class="font-semibold text-white">AI návrh</dt><dd>Ak má kino zapnutý aj AI návrh rozpisu, manažér si môže nechať navrhnúť zaradenia od služby Google Gemini. Služba nedostane tvoje meno, e-mail ani iný údaj, podľa ktorého by ťa spoznala – len náhodný kód platný pre jeden návrh, počet a&nbsp;náročnosť tvojich odpracovaných dní a&nbsp;zoznam voľných pozícií. Návrh nikoho nezaradí sám, rozhoduje manažér.</dd></div>
                    <div><dt class="font-semibold text-white">Právny základ</dt><dd>Poskytovanie aplikácie, ktorú používaš (čl. 6 ods. 1 písm. b) GDPR), pri histórii zmien môj oprávnený záujem na tom, aby sa dalo dohľadať, kto rozpis zmenil (čl. 6 ods. 1 písm. f) GDPR).</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Ak máš účet z&nbsp;pôvodnej verzie</h2>
                <p>Tvoj účet, zápisy a&nbsp;absencie som do tejto verzie preniesol z&nbsp;pôvodnej aplikácie, ktorú som tiež prevádzkoval ja. Heslo ostalo rovnaké. Evidenciu odpracovaných hodín a&nbsp;nahrané súbory som nepreniesol.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">E-maily</h2>
                <p>Aplikácia ti posiela len e-maily k&nbsp;účtu: overenie e-mailovej adresy, odkaz na obnovu hesla, pozvánku, ak ti účet vytvorí manažér, a&nbsp;oznámenie, že ťa kino schválilo. Ak má tvoje kino zapnutý rozpis zmien a&nbsp;si v&nbsp;ňom zapísaný, dostaneš aj oznámenie, keď sa rozpis zverejní alebo zmení. Nič iné ti neposielam.</p>
                <p>Ak mi napíšeš na {{ $operator['email'] }}, tvoja správa je v&nbsp;mojej schránke Gmail, ktorú prevádzkuje Google. Správy so žiadosťami k&nbsp;osobným údajom si nechám najviac 12 mesiacov po vybavení.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Bezpečnosť a&nbsp;prevádzka</h2>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>Na ochranu prihlásenia a&nbsp;formulárov pred zneužitím si aplikácia najviac hodinu pamätá počet pokusov z&nbsp;tvojej IP adresy a&nbsp;pre zadaný e-mail – uložené len ako odtlačok (hash), nie v&nbsp;čitateľnej podobe. Potom tieto záznamy vymaže, najneskôr nasledujúci deň.</li>
                    <li>Záznamy o&nbsp;tom, ktorý účet (podľa čísla) vytvoril, zmenil alebo vymazal ktorý záznam a&nbsp;ktoré polia, záznamy o&nbsp;prihláseniach, neúspešných pokusoch o&nbsp;prihlásenie, zmenách rolí a&nbsp;odmietnutých prístupoch a&nbsp;záznamy o&nbsp;chybách uchovávam {{ $logDays }} dní. IP adresa a&nbsp;e-mail zadaný pri neúspešnom prihlásení sa v&nbsp;nich ukladajú len ako odtlačok (hash). Mená, e-maily ani dôvody absencií sa do záznamov nezapisujú a&nbsp;e-mailové adresy sa zo záznamov o&nbsp;chybách automaticky odstraňujú. Záznam o&nbsp;chybe môže výnimočne obsahovať iné údaje z&nbsp;požiadavky, pri ktorej chyba vznikla.
                    <li>Ako prevádzkovateľ spravujem všetky kiná cez správu platformy. Čo v&nbsp;nej zmením, sa zaznamená (kto, kedy, v&nbsp;ktorom kine, ktorý záznam a&nbsp;ktoré polia – bez mien, e-mailov a&nbsp;dôvodov absencií) a&nbsp;tieto záznamy uchovávam 12 mesiacov.</li>
                    <li>Aby som videl, či aplikácia funguje a&nbsp;ktoré stránky sú pomalé, 7 dní uchovávam počet požiadaviek podľa účtu (jeho čísla), adresy pomalých stránok a&nbsp;technické záznamy o&nbsp;chybách a&nbsp;výkone. Vidím ich len ja.</li>
                    <li>Poskytovateľ webhostingu môže na zabezpečenie servera zaznamenávať technické údaje o&nbsp;prístupoch (IP adresu, čas, adresu stránky).</li>
                </ul>
                <p>Právnym základom je môj oprávnený záujem na bezpečnej a&nbsp;fungujúcej aplikácii (čl. 6 ods. 1 písm. f) GDPR).</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Cookies a&nbsp;úložisko prehliadača</h2>
                <p>Aplikácia používa len tieto technicky nevyhnutné cookies, ktoré podľa § 109 ods. 8 zákona č. 452/2021 Z. z. o&nbsp;elektronických komunikáciách nevyžadujú súhlas:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li><strong class="font-semibold text-white">{{ $sessionCookie }}</strong> – drží tvoje prihlásenie. Platí {{ $sessionMinutes }} minút od poslednej aktivity, potom sa relácia na serveri do hodiny vymaže.</li>
                    <li><strong class="font-semibold text-white">XSRF-TOKEN</strong> – chráni formuláre pred podvrhnutím z&nbsp;iných stránok. Platí {{ $sessionMinutes }} minút od poslednej aktivity.</li>
                    <li><strong class="font-semibold text-white">remember_web_…</strong> – len ak pri prihlásení zaškrtneš „Zapamätať si ma“. Drží prihlásenie približne 400 dní alebo do odhlásenia.</li>
                </ul>
                <p>V&nbsp;úložisku prehliadača si aplikácia pamätá, či máš v&nbsp;kalendári zapnuté zobrazenie mien a&nbsp;losovania (položky „names_checkbox“ a&nbsp;„draw_checkbox“), a&nbsp;po aktualizácii aplikácie jednu technickú značku, aby sa stránka obnovila len raz. Tieto údaje sa nikam neposielajú.</p>
                <p>Nepoužívam analytické ani reklamné cookies. Písma a&nbsp;ikony sú uložené priamo na serveri aplikácie a&nbsp;stránky nenačítavajú nič zo serverov iných firiem – prehliadaču to zakazujú aj bezpečnostné hlavičky aplikácie.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Komu údaje poskytujem</h2>
                <p>Údaje nikomu nepredávam. Spracúvajú ich pre mňa len títo sprostredkovatelia, a&nbsp;to len v&nbsp;rozsahu, ktorý potrebujú:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>poskytovateľ webhostingu{{ $hostingSuffix }}, na ktorého serveri beží aplikácia aj jej databáza,</li>
                    <li>poskytovateľ odosielania e-mailov{{ $mailProviderSuffix }},</li>
                    <li>Google (služba Gemini) – len v&nbsp;kinách so zapnutým AI návrhom rozpisu a&nbsp;len bez mien a&nbsp;kontaktov, ako je opísané vyššie.</li>
                </ul>
                <p>Manažéri tvojho kina vidia v&nbsp;aplikácii to, čo je opísané vyššie. Štátnym orgánom poskytnem údaje len vtedy, keď mi to ukladá zákon.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Prenos mimo Európskej únie</h2>
                <p>
                    Aplikácia a&nbsp;jej databáza bežia na serveri v&nbsp;Slovenskej republike.
                    @if($mailInEu)
                        Aj e-maily z&nbsp;aplikácie sa odosielajú v&nbsp;rámci Európskej únie.
                    @endif
                    Mimo Európskej únie sa môžu dostať správy, ktoré mi pošleš do schránky Gmail, a&nbsp;údaje bez mien pre AI návrh rozpisu (služba Gemini): Google ich môže spracúvať aj v&nbsp;USA. Spoločnosť Google LLC je zapojená do rámca ochrany údajov medzi EÚ a&nbsp;USA, pre ktorý Európska komisia rozhodla o&nbsp;primeranej úrovni ochrany (vykonávacie rozhodnutie (EÚ) 2023/1795).
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Automatizované rozhodovanie</h2>
                <p>O&nbsp;nikom nerozhoduje automat. Registrácie schvaľuje manažér kina. Poradie podľa odpracovaných dní a&nbsp;AI návrh v&nbsp;rozpise zmien sú len odporúčania – kto bude kde pracovať, rozhoduje manažér. Automaticky sa len mažú staré údaje podľa lehôt na tejto stránke.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Tvoje práva</h2>
                <p>Máš právo:</p>
                <ul class="list-disc space-y-1.5 pl-5">
                    <li>na prístup k&nbsp;svojim údajom a&nbsp;ich kópiu (čl. 15 GDPR),</li>
                    <li>na opravu nesprávnych údajov (čl. 16 GDPR) – meno ti opraví aj manažér kina, e-mail ja,</li>
                    <li>na vymazanie (čl. 17 GDPR) – napríklad celého účtu,</li>
                    <li>na obmedzenie spracúvania (čl. 18 GDPR),</li>
                    <li>na prenosnosť svojich údajov z&nbsp;aplikácie – pošlem ti ich v&nbsp;bežnom strojovo čitateľnom formáte (čl. 20 GDPR).</li>
                </ul>
                <p>Stačí mi napísať. Odpoviem bezplatne a&nbsp;najneskôr do jedného mesiaca. Ak si nebudem istý, že žiadosť posielaš ty, môžem ťa požiadať o&nbsp;overenie – napríklad odpoveďou z&nbsp;e-mailu, ktorý máš v&nbsp;aplikácii.</p>
            </section>

            <section class="space-y-3 rounded-lg border border-neutral-700 bg-neutral-900 p-5">
                <h2 class="text-xl font-semibold text-white">Právo namietať</h2>
                <p>Ak údaje spracúvam na základe oprávneného záujmu (ochrana prihlásenia a&nbsp;formulárov pred zneužitím, záznamy o&nbsp;chybách a&nbsp;zmenách, sledovanie pomalých stránok), môžeš kedykoľvek namietať z&nbsp;dôvodov týkajúcich sa tvojej konkrétnej situácie (čl. 21 GDPR). Spracúvanie potom ukončím, ibaže preukážem nevyhnutné oprávnené dôvody, ktoré prevažujú nad tvojimi záujmami, právami a&nbsp;slobodami.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold text-white">Sťažnosť</h2>
                <p>Ak si myslíš, že s&nbsp;tvojimi údajmi nezaobchádzam správne, môžeš podať sťažnosť (návrh na začatie konania podľa čl. 77 GDPR) na Úrad na ochranu osobných údajov Slovenskej republiky, Galvaniho 7/B, 821 04 Bratislava, <a href="https://dataprotection.gov.sk" class="text-brand-400 hover:text-brand-300" rel="noopener">dataprotection.gov.sk</a>, statny.dozor@pdp.gov.sk.</p>
            </section>

            <footer class="border-t border-neutral-800 pt-6 text-sm text-neutral-400">
                <p>Ak zmením spôsob, akým s&nbsp;údajmi zaobchádzam, upravím aj túto stránku.</p>
                <p class="mt-1">Platné od {{ $validFrom }}</p>
            </footer>
        </article>
    </div>
</x-layout>
