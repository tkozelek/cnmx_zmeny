<x-layout>
    <section class="bg-gray-700">
        <div class="flex flex-col items-center mt-20 px-6 py-8 mx-auto lg:py-0">
            <div class="w-full  rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight md:text-2xl text-white">
                        Vytvoriť účet
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{route('register.store')}}" method="POST">
                        @csrf
                        <div class="flex flex-wrap -mx-3 mb-6">
                            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                                <label for="meno" class="block mb-2 text-sm font-medium text-white">Meno</label>
                                <input type="text" value="{{old('name')}}" name="name" id="meno" autocomplete="given-name" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Juraj">
                                @error('name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="w-full md:w-1/2 px-3">
                                <label for="priezvisko" class="block mb-2 text-sm font-medium text-white">Priezvisko</label>
                                <input type="text" value="{{old('lastname')}}" name="lastname" id="priezvisko" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Hruška">
                                @error('lastname')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-white">E-mail</label>
                            <input type="email" value="{{old('email')}}" name="email" id="email" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="j.hruska@gmail.com" >
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-white">Heslo</label>
                            <input type="password"  name="password" id="password" placeholder="Heslo" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" >
                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="confirm-password" class="block mb-2 text-sm font-medium text-white">Potvrď heslo</label>
                            <input type="password"  name="password_confirmation" id="confirm-password" placeholder="Heslo znova" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
                            @error('password_confirmation')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase">Registrovať</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout>
