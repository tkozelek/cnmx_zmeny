<div class="mt-10 md:mt-12 mb-8">
    <h3 class="text-lg sm:text-xl font-semibold text-slate-100 mb-4">Absencie v aktuálnom týždni</h3>
    <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-300">
                <thead class="text-xs text-slate-200 uppercase bg-slate-700/60">
                <tr>
                    <th scope="col" class="px-6 py-3 font-medium">Meno</th>
                    <th scope="col" class="px-6 py-3 font-medium">Začiatok</th>
                    <th scope="col" class="px-6 py-3 font-medium">Koniec</th>
                    <th scope="col" class="px-6 py-3 font-medium">Dôvod</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                @foreach($absences as $absence)
                    <tr class="hover:bg-slate-700/40 transition-colors duration-150">
                        <td class="px-6 py-3 font-medium text-slate-100">{{ $absence->user }}</td>
                        <td class="px-6 py-3 whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</td>
                        <td class="px-6 py-3">{{ $absence->popis }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
