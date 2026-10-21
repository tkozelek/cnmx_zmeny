<form class="space-y-4" action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form-input
            name="name"
            id="create_name"
            label="Meno"
            :value="old('name')"
            icon="fa-user"
            required
        />

        <x-form-input
            name="lastname"
            id="create_lastname"
            label="Priezvisko"
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
        :value="old('email')"
        icon="fa-envelope"
        required
    />

    <div class="relative">
        <label for="create_roles" class="block mb-2 text-sm font-medium text-neutral-200">Rola</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                <i class="fa-solid fa-id-badge"></i>
            </div>
            <select
                name="role"
                id="create_roles"
                @error('role') aria-invalid="true" aria-describedby="create_roles-error" @enderror
                class="w-full min-h-12 px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-500 text-white transition-all duration-200 text-base sm:text-sm shadow-inner focus:outline-none focus:border-brand-500 hover:border-neutral-400 appearance-none cursor-pointer"
            >
                @isset($roles)
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" class="bg-neutral-900 text-white" @selected(old('role', \App\Enums\Role::Employee->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                @endisset
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-neutral-400">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
        </div>
        @error('role')
            <p id="create_roles-error" class="text-rose-400 text-sm mt-1.5 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
        @enderror
    </div>

    <div class="pt-2">
        <button
            type="submit"
            class="w-full min-h-12 py-3 px-5 bg-neutral-100 hover:bg-white text-neutral-900 font-bold rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2"
        >
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Vytvoriť používateľa</span>
        </button>
    </div>
</form>
