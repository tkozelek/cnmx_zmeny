<form class="space-y-4 md:space-y-6" action="{{ route('admin.users.store') }}" method="POST">
    @csrf
    <div class="flex flex-wrap -mx-3 mb-4">
        <div class="w-full md:w-1/2 px-3 mb-4 md:mb-0">
            <label for="meno" class="block mb-2 text-sm font-medium text-slate-200">Meno</label>
            <input type="text" value="{{ old('name') }}" name="name" id="meno" autocomplete="given-name" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="Juraj">
            @error('name')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="w-full md:w-1/2 px-3">
            <label for="priezvisko" class="block mb-2 text-sm font-medium text-slate-200">Priezvisko</label>
            <input type="text" value="{{ old('lastname') }}" name="lastname" id="priezvisko" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="Hruška">
            @error('lastname')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div>
        <label for="email" class="block mb-2 text-sm font-medium text-slate-200">E-mail</label>
        <input type="email" value="{{ old('email') }}" name="email" id="email" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="j.hruska@gmail.com">
        @error('email')
            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="roles" class="block mb-2 text-sm font-medium text-slate-200">Vyber možnosť:</label>
        <select name="role" id="roles" class="bg-slate-800 border border-slate-700 text-slate-100 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
            @isset($roles)
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', \App\Enums\Role::Employee->value) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            @endisset
        </select>
        @error('role')
            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg py-3 font-semibold tracking-wider uppercase transition-colors">Vytvoriť</button>
</form>
