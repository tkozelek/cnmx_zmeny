<div class="mt-10 md:mt-12 mb-8">
    <h3 class="text-lg sm:text-xl font-semibold text-slate-100 mb-4">Prehľad počtu zapísaných</h3>
    <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden max-w-md mx-auto sm:mx-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-300">
                <thead class="text-xs text-slate-200 uppercase bg-slate-700/60">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Meno</th>
                    <th scope="col" class="px-4 py-3 font-medium text-center">Počet</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                @foreach($userCount as $user)
                    <tr class="hover:bg-slate-700/40 transition-colors duration-150">
                        <td class="px-4 py-3 font-medium text-slate-100">{{ $user->name ?? $user }}</td>
                        <td class="px-4 py-3 text-center">{{ $user->count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
