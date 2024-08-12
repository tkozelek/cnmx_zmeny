<x-layout>
    <div class="xl:container mx-auto mt-5">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <!-- left -->
            <div class="col col-span-1 p-4 text-white">
                <p class="text-2xl font-bold tracking-wider">Použivateľ</p>
                <hr>
                <x-user-info :text="'Meno'">{{ $user->name }}</x-user-info>
                <x-user-info :text="'Priezvisko'">{{ $user->lastname }}</x-user-info>
                <x-user-info :text="'E-mail'">{{ $user->email }}</x-user-info>
                <x-user-info :text="'Rola'">{{ $user->role->text }}</x-user-info>
                <x-user-info :text="'Vytvorený'">{{ $user->created_at }}</x-user-info>
                <x-user-info :text="'Upravený'">{{ $user->updated_at }}</x-user-info>
                <x-user-info :text="'Posledne prihlásenie'">{{ isset($user->last_login_at) ? $user->getDate($user->last_login_at) : '-' }}</x-user-info>
            </div>

            <!-- right -->
            <div class="col-span-1 md:col-span-3 lg:col-span-4 p-4 text-white border-black border-2">
                <section>
                    <div class="float-end">
                        <select name="date" id="date" class="border text-sm rounded-lg w-full p-2.5 bg-gray-600 border-gray-500 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
                            <option>Celá doba</option>
                            <option>Mesiac</option>
                            <option>Rok</option>
                        </select>
                    </div>
                </section>
                <p class="text-2xl ">Frekvencia zapisovaných dni v danom období</p>
                <p class="text-xl mb-3 text-gray-300">Za dané časové obdobie má zapísaných: x dni</p>
                <section class="bg-gray-300">
                    <div class="">
                        {!! $chartuserdays->container() !!}
                        {!! $chartuserdays->script() !!}
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layout>

