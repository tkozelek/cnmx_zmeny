<x-layout>
    <section class=" text-white font-sans">
        <div class="flex flex-col items-center px-6 py-8 mx-auto">

            <div class="w-full max-w-md mt-20 bg-gray-800 rounded-2xl shadow-lg border border-gray-700">
                <div class="p-8 space-y-6">
                    <div class="text-center">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight">
                            Vytvoriť účet
                        </h1>
                        <p class="text-gray-400 mt-2">Pridajte sa k nám a začnite ešte dnes.</p>
                    </div>

                    <form class="space-y-4" action="{{ route('register.store') }}" method="POST">
                        @csrf

                        <div class="flex flex-col sm:flex-row sm:space-x-4">
                            <div class="w-full sm:w-1/2">
                                <x-form-input
                                    name="name"
                                    label="Meno"
                                    placeholder="Juraj"
                                    :value="old('name')"
                                    icon="fa-user"
                                    required
                                />
                            </div>
                            <div class="w-full sm:w-1/2 mt-4 sm:mt-0">
                                <x-form-input
                                    name="lastname"
                                    label="Priezvisko"
                                    placeholder="Hruška"
                                    :value="old('lastname')"
                                    icon="fa-user"
                                    required
                                />
                            </div>
                        </div>

                        <x-form-input
                            type="email"
                            name="email"
                            label="E-mail"
                            placeholder="vas@email.com"
                            :value="old('email')"
                            icon="fa-envelope"
                            required
                        />

                        <x-form-input
                            type="password"
                            name="password"
                            label="Heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />

                        <x-form-input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            label="Potvrďte heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />

                        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-800 font-bold rounded-lg text-sm px-5 py-3 text-center uppercase tracking-wider transition duration-200">
                            Registrovať
                        </button>

                        <p class="text-sm text-center text-gray-400">
                            Už máte účet? <a href="{{ route('login') }}" class="font-medium text-blue-500 hover:underline">Prihláste sa</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout>
