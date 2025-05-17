<div>
    <div class="container mx-auto">
        <div class="relative shadow-md sm:rounded-lg min-h-80">
            <div class="flex flex-column sm:flex-row flex-wrap sm:space-y-0 items-center justify-between bg-gray-800 p-2">
                <div>
                    <button id="dropdownRadioButton" data-dropdown-toggle="dropdownRadio" class="inline-flex items-center border focus:outline-none focus:ring-4 font-medium rounded-lg text-sm px-3 py-1.5 bg-gray-800 text-white border-gray-600 hover:bg-gray-700 hover:border-gray-600 focus:ring-gray-700" type="button">
                        Role
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="dropdownRadio" class="z-10 hidden w-48 divide-y rounded-lg shadow bg-gray-700 divide-gray-600" data-popper-reference-hidden="" data-popper-escaped="" data-popper-placement="top" style="position: absolute; inset: auto auto 0px 0px; margin: 0px; transform: translate3d(522.5px, 3847.5px, 0px);">
                        <ul class="p-3 space-y-1 text-sm text-gray-200" aria-labelledby="dropdownRadioButton">
                            <li>
                                <div class="flex items-center p-2 rounded  hover:bg-gray-600">
                                    <input selected wire:model.live="selectedRole" id="role-0" type="radio" value="0" name="filter-radio" class="w-4 h-4 text-blue-600 focus:ring-blue-600 ring-offset-gray-800 focus:ring-offset-gray-800 focus:ring-2 bg-gray-700 border-gray-600">
                                    <label for="role-0" class="w-full ms-2 text-sm font-medium  rounded text-gray-300">Všetky</label>
                                </div>
                            </li>
                            @isset($roles)
                                @foreach($roles as $role)
                                    <li>
                                        <div class="flex items-center p-2 rounded  hover:bg-gray-600">
                                            <input wire:model.live="selectedRole" id="role-{{ $role->id }}" type="radio" value="{{ $role->id }}" name="filter-radio" class="w-4 h-4 text-blue-600 focus:ring-blue-600 ring-offset-gray-800 focus:ring-offset-gray-800 focus:ring-2 bg-gray-700 border-gray-600">
                                            <label for="role-{{ $role->id }}" class="w-full ms-2 text-sm font-medium  rounded text-gray-300">{{ $role->text }}</label>
                                        </div>
                                    </li>
                                @endforeach
                            @endisset
                        </ul>
                    </div>
                </div>
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-5 h-5  text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" wire:model.live="search" id="table-search" class="block p-2 ps-10 text-sm w-60  border rounded-lg bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Vyhladaj meno...">
                </div>
            </div>
            <div class="relative overflow-x-auto mb-1">
                <table class="w-full  text-sm text-left rtl:text-right  text-gray-400">
                    <thead class="text-xs uppercase bg-gray-900 text-gray-400">
                    <tr>
                        <x-table-cell-header-sortby :sortby="'name'">Meno</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'email'">E-mail</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'id_role'">Rola</x-table-cell-header-sortby>
                        <x-table-cell-header-sortby :sortby="'updated_at'">Posledná úprava</x-table-cell-header-sortby>
                        <x-table-cell-header>Akcia</x-table-cell-header>
                    </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-75">
                    @if(isset($users) && count($users) > 0)
                        @foreach($users as $user)
                            <x-table-row>
                                <x-table-cell-header class="px-6 py-4 font-medium whitespace-nowrap text-white">
                                    <a href="{{ route('profile.show', ['user' => $user->id]) }}">{{ $user->lastname.' '.$user->name }}</a>
                                </x-table-cell-header>
                                <x-table-cell>{{ $user->email }}</x-table-cell>
                                <x-table-cell>{{ $user->role->text }}</x-table-cell>
                                <x-table-cell>{{ $user->updated_at }}</x-table-cell>
                                @if($user->id_role == config('constants.roles.neovereny'))
                                    <x-table-cell class="flex justify-content-between">
                                        <button wire:click="accept({{$user->id}})" class="px-2 py-1 mr-1 rounded-xl bg-green-800 hover:bg-green-700"><i class="fa-solid fa-check"></i></button>
                                        <button wire:click="deny({{$user->id}})" class="px-2 py-1 ml-1 rounded-xl bg-red-800 hover:bg-orange-700"><i class="fa-solid fa-x"></i></button>
                                    </x-table-cell>
                                @else
                                    <x-table-cell class="font-medium text-blue-500 hover:underline"><a href="{{ route('admin.users.edit', ['user' => $user->id]) }}">Upraviť</a></x-table-cell>
                                @endif
                            </x-table-row>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</div>
