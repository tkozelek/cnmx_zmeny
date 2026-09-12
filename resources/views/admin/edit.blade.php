@props(['roles'])

<x-layout title="Upraviť používateľa">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 flex justify-center">
        <div class="w-full max-w-lg bg-neutral-900/90 backdrop-blur-md border border-neutral-800 rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-neutral-800">
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-neutral-800 border border-neutral-700 text-sky-400 text-base">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold tracking-tight text-white">Upraviť používateľa</h1>
                        <p class="text-xs text-neutral-400">{{ $user->name }} {{ $user->lastname }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-neutral-400 hover:text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Späť na zoznam</span>
                </a>
            </div>

            <form class="space-y-4" action="{{ route('admin.users.update', ['user' => $user->id]) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input
                        name="name"
                        id="meno"
                        label="Meno"
                        placeholder="Juraj"
                        :value="old('name', $user->name)"
                        icon="fa-user"
                        required
                    />

                    <x-form-input
                        name="lastname"
                        id="priezvisko"
                        label="Priezvisko"
                        placeholder="Hruška"
                        :value="old('lastname', $user->lastname)"
                        icon="fa-user"
                        required
                    />
                </div>

                <x-form-input
                    type="email"
                    name="email"
                    id="email"
                    label="E-mail"
                    placeholder="j.hruska@gmail.com"
                    :value="old('email', $user->email)"
                    icon="fa-envelope"
                    required
                />

                <div class="relative">
                    <label for="roles" class="block mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">Rola</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-500">
                            <i class="fa-solid fa-id-badge"></i>
                        </div>
                        <select
                            name="role"
                            id="roles"
                            class="w-full px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 transition-all duration-200 text-sm shadow-inner focus:outline-none focus:ring-2 focus:ring-sky-500/60 focus:border-sky-500 hover:border-neutral-700 appearance-none cursor-pointer"
                        >
                            @isset($roles)
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" class="bg-neutral-900 text-white" @selected($user->hasRole($role->value))>{{ $role->label() }}</option>
                                @endforeach
                            @endisset
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-neutral-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    @error('role')
                        <p class="text-rose-400 text-xs mt-1.5 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        name="btn_edit_save"
                        class="w-full py-3 px-5 bg-neutral-100 hover:bg-white text-neutral-900 font-bold rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Uložiť zmeny</span>
                    </button>
                </div>
            </form>

            <div class="pt-4 border-t border-neutral-800">
                <form action="{{ route('admin.users.destroy', ['user' => $user->id]) }}" method="POST">
                    @method('DELETE')
                    @csrf
                    <button
                        type="submit"
                        class="w-full py-2.5 px-4 text-rose-400 hover:text-white bg-rose-500/10 hover:bg-rose-600/20 border border-rose-500/30 rounded-xl text-xs font-semibold tracking-wide transition flex items-center justify-center gap-2"
                        onclick="return confirm('Určite chceš deaktivovať tento účet?');"
                    >
                        <i class="fa-solid fa-user-slash text-xs"></i>
                        <span>Deaktivovať účet</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
