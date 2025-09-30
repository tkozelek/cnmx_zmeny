<div>
    <div class="md:container mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="bg-gray-800 shadow-xl rounded-lg overflow-hidden">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-gray-700/50 border-b border-gray-700">
                <div>
                    <button id="dropdownRadioButton" data-dropdown-toggle="dropdownRadio" class="inline-flex items-center text-gray-300 bg-gray-700 hover:bg-gray-600 border border-gray-600 focus:ring-4 focus:outline-none focus:ring-gray-600 font-medium rounded-lg text-sm px-4 py-2.5 text-center" type="button">
                        Filtrovať podľa role
                        <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownRadio" class="z-50 hidden w-56 divide-y divide-gray-600 rounded-lg shadow-lg bg-gray-700" data-popper-reference-hidden="" data-popper-escaped="" data-popper-placement="top" style="position: absolute; inset: auto auto 0px 0px; margin: 0px; transform: translate3d(522.5px, 3847.5px, 0px);">
                        <ul class="p-3 space-y-2 text-sm text-gray-200" aria-labelledby="dropdownRadioButton">
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-600">
                                    <input selected wire:model.live="selectedRole" id="role-0" type="radio" value="0" name="filter-radio" class="w-4 h-4 text-blue-500 bg-gray-600 border-gray-500 focus:ring-blue-500 focus:ring-2 ring-offset-gray-700">
                                    <label for="role-0" class="w-full ms-2 text-sm font-medium rounded text-gray-300 cursor-pointer">Všetky role</label>
                                </div>
                            </li>
                            @isset($roles)
                                @foreach($roles as $role)
                                    <li>
                                        <div class="flex items-center p-2 rounded hover:bg-gray-600">
                                            <input wire:model.live="selectedRole" id="role-{{ $role->id }}" type="radio" value="{{ $role->id }}" name="filter-radio" class="w-4 h-4 text-blue-500 bg-gray-600 border-gray-500 focus:ring-blue-500 focus:ring-2 ring-offset-gray-700">
                                            <label for="role-{{ $role->id }}" class="w-full ms-2 text-sm font-medium rounded text-gray-300 cursor-pointer">{{ $role->text }}</label>
                                        </div>
                                    </li>
                                @endforeach
                            @endisset
                        </ul>
                    </div>
                </div>
                <div class="relative w-full md:w-auto">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" id="table-search" class="block w-full md:w-80 p-2.5 ps-10 text-sm text-white border border-gray-600 rounded-lg bg-gray-700 placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Vyhľadať používateľa...">
                </div>
            </div>

            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-700 sticky top-0 z-[5]"> {{-- Sticky hlavička --}}
                    <tr>
                        <x-table-cell-header-sortby :sortby="'name'">Meno</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'email'">E-mail</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'id_role'">Rola</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'updated_at'">Posledná aktivita</x-table-cell-header-sortby>
                        <x-table-cell-header class="text-center">Akcie</x-table-cell-header>
                    </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50 transition-opacity duration-300" class="divide-y divide-gray-700">
                    @forelse($users as $user)
                        <x-table-row class="hover:bg-gray-700/50 transition-colors duration-150">
                            <x-table-cell-header class="px-6 py-4 font-semibold whitespace-nowrap text-white">
                                <a href="{{ route('profile.show', ['user' => $user->id]) }}" class="hover:text-blue-400 transition-colors">{{ $user->lastname.' '.$user->name }}</a>
                            </x-table-cell-header>
                            <x-table-cell>{{ $user->email }}</x-table-cell>
                            <x-table-cell>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($user->role->name === config('constants.roles.admin')) bg-red-700 text-red-100
                                        @elseif($user->role->name === config('constants.roles.neovereny')) bg-yellow-600 text-yellow-100
                                        @else bg-sky-700 text-sky-100
                                        @endif">
                                        {{ $user->role->text }}
                                    </span>
                            </x-table-cell>
                            <x-table-cell>{{ $user->updated_at }}</x-table-cell>
                            <x-table-cell>
                                <div class="flex items-center justify-center gap-2">
                                    @if($user->id_role == config('constants.roles.neovereny'))
                                        <button wire:click="accept({{$user->id}})" title="Schváliť" class="p-2 rounded-md bg-green-600 hover:bg-green-500 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                            <i class="fa-solid fa-check w-4 h-4"></i>
                                        </button>
                                        <button wire:click="deny({{$user->id}})" title="Zamietnuť" class="p-2 rounded-md bg-red-600 hover:bg-red-500 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                                            <i class="fa-solid fa-x w-4 h-4"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('admin.users.edit', ['user' => $user->id]) }}" title="Upraviť používateľa" class="p-2 rounded-md bg-blue-600 hover:bg-blue-500 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                                            <i class="fa-solid fa-pen-to-square w-4 h-4"></i> Upraviť
                                        </a>
                                    @endif
                                </div>
                            </x-table-cell>
                        </x-table-row>
                    @empty
                        <x-table-row>
                            <x-table-cell colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center text-gray-500">
                                    <i class="fa-solid fa-users-slash text-4xl mb-3"></i>
                                    <span class="text-lg">Nenašli sa žiadni používatelia.</span>
                                    @if(empty($search) && $selectedRole == 0)
                                        <span class="text-sm">Skúste zmeniť filtre alebo pridať nových používateľov.</span>
                                    @else
                                        <span class="text-sm">Skúste upraviť kritériá vyhľadávania alebo filtrovania.</span>
                                    @endif
                                </div>
                            </x-table-cell>
                        </x-table-row>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="p-5 bg-gray-700/50 border-t border-gray-700">
                    {{ $users->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>
