@php
    $benefits = [
        ['icon' => 'fa-calendar-check', 'title' => 'Dni si vyberáš ty', 'text' => 'Vyznač, kedy môžeš pracovať popri škole či inej brigáde – manažér hneď vidí, s kým môže počítať.'],
        ['icon' => 'fa-mobile-screen-button', 'title' => 'Vždy v telefóne', 'text' => 'Rozpis máš na mobile aj počítači, kedykoľvek si ho potrebuješ pozrieť.'],
        ['icon' => 'fa-umbrella-beach', 'title' => 'Absenciu nahlásiš za minútu', 'text' => 'Dovolenku či skúškové zapíšeš vopred a manažér ju uvidí priamo v kalendári.'],
        ['icon' => 'fa-eye', 'title' => 'Rovnaký rozpis pre všetkých', 'text' => 'Celý tím vidí to isté, takže nikto sa nemusí pýtať, čo platí.'],
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
                    :title="$benefit['title']"
                    :text="$benefit['text']"
                    data-tilt
                    style="transition-delay: {{ $i * 100 }}ms;"
                />
            @endforeach
        </div>
    </div>
</section>
