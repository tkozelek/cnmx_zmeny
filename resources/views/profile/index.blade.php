<x-layout>
    <div class="container mx-auto mt-5">
        <div class="grid grid-cols-4 gap-4">
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
                <x-user-info :text="'Posledne prihlásenie'">{{ $user->getDate($user->last_login_at) }}</x-user-info>
            </div>

            <!-- right -->
            <div class="col-span-3 p-4 text-white border-black border-2">
                <section>
                    <div class="float-end">
                        <select name="date" id="date" class="border text-sm rounded-lg w-full p-2.5 bg-gray-600 border-gray-500 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
                            <option>Celá doba</option>
                            <option>Mesiac</option>
                            <option>Rok</option>
                        </select>
                    </div>
                </section>
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

