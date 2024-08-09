<x-layout>
    <section class="bg-gray-700">
        <div class="flex flex-col items-center mt-20 px-6 py-8 mx-auto lg:py-0">
            <div class="w-full rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight  md:text-2xl text-white">
                        Zabudnuté heslo
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{ route('password.send') }}" method="POST">
                        @csrf
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium  text-white">E-mailová adresa</label>
                            <input type="text" name="email" id="email" placeholder="E-mailová adresa" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" >
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase">odoslať</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout>
