<section id="login" class="py-20 sm:py-28 bg-gray-900">
    <div class="container mx-auto px-4 text-center">
        <div class="reveal">
            <h3 class="text-3xl md:text-4xl font-bold mb-4">Pripravení pridať sa?</h3>
            <p class="text-gray-400 mb-8 max-w-xl mx-auto">Vytvorte si účet alebo sa prihláste a začnite plánovať svoje zmeny ešte dnes.</p>
        </div>

        <div class="reveal max-w-sm mx-auto bg-gray-800 p-8 rounded-lg border border-gray-700" style="transition-delay: 200ms;">
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="{{ route('login') }}" class="cta-button w-full bg-gray-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-gray-500">
                    Prihlásiť sa
                </a>
                <a href="{{ route('register') }}" class="cta-button w-full bg-amber-500 text-gray-900 font-bold py-3 px-4 rounded-lg">
                    Vytvoriť účet
                </a>
            </div>
        </div>
    </div>
</section>
