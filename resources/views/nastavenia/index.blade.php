<x-layout>
    <section class="bg-gray-700">
        <div class="flex flex-col items-center mt-20 px-6 py-8 mx-auto lg:py-0">
            <div class="w-full rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight md:text-2xl text-white">
                        Nastavenia
                    </h1>
                    <br>
                    <a href="{{ route('holiday.index') }}" class="my-2">
                        <div class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase text-center">
                            dovolenka
                        </div>
                    </a>
                    <br>
                    <a href="{{ route('settings.password') }}" class="my-2">
                        <div class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase text-center">
                            zmeniť heslo
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
