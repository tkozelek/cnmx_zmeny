@php
    $benefits = [
        ['icon' => 'fa-calendar-check', 'accent' => 'sky', 'title' => 'Píšeš si dni sám', 'text' => 'Vyznač, kedy môžeš pracovať okolo školy či brigády, a manažér z toho poskladá rozpis.'],
        ['icon' => 'fa-mobile-screen-button', 'accent' => 'emerald', 'title' => 'Vždy v telefóne', 'text' => 'Rozpis máš na mobile aj počítači, kedykoľvek si ho potrebuješ pozrieť.'],
        ['icon' => 'fa-envelope', 'accent' => 'amber', 'title' => 'Vieš o zmenách hneď', 'text' => 'Keď manažér zverejní alebo upraví rozpis, príde ti e-mail.'],
        ['icon' => 'fa-eye', 'accent' => 'rose', 'title' => 'Rovnaký rozpis pre všetkých', 'text' => 'Celý tím vidí to isté, takže nikto sa nemusí pýtať, čo platí.'],
    ];
@endphp

<section id="vyhody" class="bg-neutral-950 pb-24 sm:pb-32">
    <div class="container mx-auto px-4">
        <div class="reveal mb-12 text-center">
            <h2 class="mb-3 text-3xl font-bold text-neutral-100 md:text-4xl">
                <span class="split-word"><span class="split-word-inner">Čo z toho máš</span></span>
            </h2>
            <p class="mx-auto max-w-2xl text-neutral-400">Menej dohadovania okolo zmien, viac istoty, kedy naozaj robíš.</p>
        </div>

        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-2">
            @foreach($benefits as $i => $benefit)
                <x-card-with-icon
                    :icon="$benefit['icon']"
                    :accent="$benefit['accent']"
                    :title="$benefit['title']"
                    :text="$benefit['text']"
                    data-tilt
                    style="transition-delay: {{ $i * 100 }}ms;"
                />
            @endforeach
        </div>
    </div>
</section>
