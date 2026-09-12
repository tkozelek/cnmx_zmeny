<form class="space-y-4" action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form-input
            name="name"
            id="create_name"
            label="Meno"
            placeholder="Juraj"
            :value="old('name')"
            icon="fa-user"
            required
        />

        <x-form-input
            name="lastname"
            id="create_lastname"
            label="Priezvisko"
            placeholder="Hruška"
            :value="old('lastname')"
            icon="fa-user"
            required
        />
    </div>

    <x-form-input
        type="email"
        name="email"
        id="create_email"
        label="E-mail"
        placeholder="j.hruska@gmail.com"
        :value="old('email')"
        icon="fa-envelope"
        required
    />

    <div class="relative">
        <label for="create_roles" class="block mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">Rola</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-500">
                <i class="fa-solid fa-id-badge"></i>
            </div>
            <select
                name="role"
                id="create_roles"
                class="w-full px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 transition-all duration-200 text-sm shadow-inner focus:outline-none focus:ring-2 focus:ring-sky-500/60 focus:border-sky-500 hover:border-neutral-700 appearance-none cursor-pointer"
            >
                @isset($roles)
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" class="bg-neutral-900 text-white" @selected(old('role', \App\Enums\Role::Employee->value) === $role->value)>{{ $role->label() }}</option>
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
            class="w-full py-3 px-5 bg-neutral-100 hover:bg-white text-neutral-900 font-bold rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2"
        >
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Vytvoriť používateľa</span>
        </button>
    </div>
</form>
