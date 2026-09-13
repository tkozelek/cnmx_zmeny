@php
    $benefits = [
        ['icon' => 'fa-scale-balanced', 'accent' => 'sky', 'title' => 'Work-Life Balance', 'text' => 'Jednoducho si naplánujte prácu okolo školy, koníčkov a rodiny. Koniec stresu z organizácie času.'],
        ['icon' => 'fa-clock', 'accent' => 'emerald', 'title' => 'Prístup 24/7', 'text' => 'Celá aplikácia je dostupná prakticky nonstop. Celú noc až deň, aj cez víkend.'],
        ['icon' => 'fa-mobile-screen-button', 'accent' => 'amber', 'title' => 'Prístup odkiaľkoľvek', 'text' => 'Či už ste doma alebo na cestách, svoj rozvrh máte vždy po ruke v mobile alebo na počítači.'],
        ['icon' => 'fa-eye', 'accent' => 'rose', 'title' => 'Transparentnosť', 'text' => 'Všetci majú rovnaký prístup k informáciám. Systém je férový a prehľadný pre celý tím.'],
    ];
@endphp

<section id="vyhody" class="bg-neutral-900/40 py-20 sm:py-28">
    <div class="container mx-auto px-4">
        <div class="reveal mb-12 text-center">
            <h2 class="mb-2 text-3xl font-bold text-neutral-100 md:text-4xl">Navrhnuté pre <span class="gradient-text">váš komfort</span></h2>
            <p class="mx-auto max-w-2xl text-neutral-400">Získajte viac než len rozvrh. Získajte slobodu a prehľadnosť.</p>
        </div>

        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-2">
            @foreach($benefits as $i => $benefit)
                <x-card-with-icon
                    :icon="$benefit['icon']"
                    :accent="$benefit['accent']"
                    :title="$benefit['title']"
                    :text="$benefit['text']"
                    style="transition-delay: {{ $i * 100 }}ms;"
                />
            @endforeach
        </div>
    </div>
</section>
