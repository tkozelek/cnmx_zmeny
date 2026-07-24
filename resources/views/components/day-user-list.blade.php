{{-- Also rendered on its own by DayUserController::toggleUser; app.js swaps `.users-container`. --}}
@props(['day'])

<div class="users-container divide-y divide-slate-700/60">
    @forelse($day->users as $user)
        <x-day-user-row :day="$day" :user="$user"/>
    @empty
        <p class="rows px-2 py-3 text-center text-xs italic text-slate-500">Nikto nie je zapísaný.</p>
    @endforelse
</div>
