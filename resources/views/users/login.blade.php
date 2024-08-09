<x-layout>
    <section class="bg-gray-700">
        <div class="flex flex-col items-center mt-20 px-6 py-8 mx-auto lg:py-0">
            <div class="w-full rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-white md:text-2xl">
                        Prihlásenie
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{ route('login.auth') }}" method="POST">
                        @csrf
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-white">E-mailová adresa</label>
                            <input type="text" value="{{ old('email') }}" name="email" id="email" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Email">
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-white">Heslo</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" placeholder="Heslo" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" class="eyebutton absolute inset-y-0 right-0 flex items-center px-3 text-gray-600 focus:outline-none" id="togglePassword">
                                    <i id="eyeIcon" class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember" type="checkbox" name="remember" value="" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-600 ring-offset-gray-800 focus:ring-2 bg-gray-700 border-gray-600">
                                <label for="remember" class="ms-2 text-sm font-medium text-gray-300">Automatické prihlásenie</label>
                            </div>
                            <a href="{{ route('password.index') }}" class="text-sm font-medium text-blue-500 hover:underline">Zabudnuté heslo?</a>
                        </div>
                        <button type="submit" class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase">Prihlásiť</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout>
