<x-layout>
    <div class="container mx-auto mt-5 mb-2">
        <div class="flex justify-between items-center mb-2">
            <div></div>
            <button data-modal-target="default-modal" data-modal-toggle="default-modal" class="block text-white focus:ring-4 focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 text-center bg-blue-600 hover:bg-blue-700 focus:ring-blue-800" type="button">
                <i class="fa-solid fa-user-plus"></i> Pridať použivateľa
            </button>
        </div>
        <div id="default-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <!-- Modal content -->
                <div class="relative rounded-lg shadow bg-gray-700">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-600">
                        <button type="button" class="text-gray-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-gray-600 hover:text-white" data-modal-hide="default-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <form class="space-y-4 md:space-y-6" action="{{route('admin.pouzivatelia.add')}}" method="POST">
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
                                <label for="roles" class="block mb-2 text-sm font-medium  text-white">Vyber možnosť:</label>
                                <select name="id_role" id="roles" class="border  text-sm rounded-lg  block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
                                    @isset($roles)
                                        @foreach($roles as $role)
                                            <option value="{{$role->id}}">{{$role->text}}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                            <button type="submit" class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase">vytvoriť</button>
                        </form>
                    </div>
                    <!-- Modal footer -->
                </div>
            </div>
        </div>
    </div>
    <!-- LIVEWIRE -->
    <div>
        <livewire:user-table :roles="$roles"/>
    </div>

</x-layout>
