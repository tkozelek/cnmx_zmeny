@props(['roles'])

<x-layout>
    <div class="container mx-auto px-4 py-12 flex justify-center">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-xl shadow-xl p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-bold tracking-wide text-white uppercase mb-6">
                Upraviť používateľa
            </h1>
            <form class="space-y-5" action="{{ route('admin.users.update', ['user' => $user->id]) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="meno" class="block mb-2 text-sm font-medium text-slate-200">Meno</label>
                        <input type="text" value="{{ old('name', $user->name) }}" name="name" id="meno" autocomplete="given-name" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="Juraj">
                        @error('name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="priezvisko" class="block mb-2 text-sm font-medium text-slate-200">Priezvisko</label>
                        <input type="text" value="{{ old('lastname', $user->lastname) }}" name="lastname" id="priezvisko" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="Hruška">
                        @error('lastname')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-slate-200">E-mail</label>
                    <input type="email" value="{{ old('email', $user->email) }}" name="email" id="email" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="j.hruska@gmail.com">
                    @error('email')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="roles" class="block mb-2 text-sm font-medium text-slate-200">Rola:</label>
                    <select name="role" id="roles" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                        @isset($roles)
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" @if($user->hasRole($role->value)) selected @endif>{{ $role->label() }}</option>
                            @endforeach
                        @endisset
                    </select>
                    @error('role')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" name="btn_edit_save" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg py-3 font-semibold tracking-wider uppercase transition-colors">Uložiť</button>
            </form>
            <form class="mt-4" action="{{ route('admin.users.destroy', ['user' => $user->id]) }}" method="POST">
                @method('DELETE')
                @csrf
                <button type="submit" class="w-full text-red-400 hover:text-white bg-red-950/50 hover:bg-red-900 border border-red-900/50 rounded-lg py-2.5 font-semibold tracking-wider uppercase transition-colors" onclick="return confirm('Určite chceš deaktivovať tento účet?');">Deaktivovať účet</button>
            </form>
        </div>
    </div>
</x-layout>
