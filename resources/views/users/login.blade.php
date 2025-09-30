<x-layout>
    <section class="text-white font-sans">
        <div class="flex flex-col items-center px-6 py-8 mx-auto">

            <div class="w-full max-w-md mt-20 bg-gray-800 rounded-2xl shadow-2xl border border-gray-700">
                <div class="p-8 space-y-6">
                    <div class="text-center">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight">
                            Vitajte späť!
                        </h1>
                        <p class="text-gray-400 mt-2">Zadajte svoje údaje pre prihlásenie.</p>
                    </div>

                    <form class="space-y-6" action="{{ route('login.auth') }}" method="POST">
                        @csrf

                        <x-form-input
                            type="email"
                            name="email"
                            label="E-mailová adresa"
                            placeholder="vas@email.com"
                            :value="old('email')"
                            icon="fa-envelope"
                            required
                            autofocus
                        />

                        <x-form-input
                            type="password"
                            name="password"
                            label="Heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />

                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="remember" name="remember" type="checkbox" class="w-4 h-4 border border-gray-600 rounded bg-gray-700 focus:ring-3 focus:ring-blue-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="remember" class="text-gray-400">Automatické prihlásenie</label>
                                </div>
                            </div>
                            <a href="{{ route('password.index') }}" class="text-sm font-medium text-blue-500 hover:underline">Zabudnuté heslo?</a>
                        </div>

                        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-800 font-bold rounded-lg text-sm px-5 py-3 text-center uppercase tracking-wider transition duration-200">
                            Prihlásiť
                        </button>
                    </form>
                    <p class="text-sm text-center text-gray-300">Nemáš ešte účet? <a class="text-blue-500 font-bold" href="{{ route('register') }}">Vytvor si ho!</a></p>
                </div>
            </div>
        </div>
    </section>
</x-layout>
