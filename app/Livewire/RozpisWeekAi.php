<?php

namespace App\Livewire;

use App\Models\PositionSlot;
use App\Models\Team;
use App\Services\AiRozpisSuggestionService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The one button that drafts the whole week, in the builder's header.
 *
 * Holds no draft state of its own: it makes the call and hands each day its own placements over
 * an event, so the seven RozpisDay components keep owning their suggestions and the existing
 * accept/dismiss flow is reused untouched. Nothing is written here — a suggestion becomes an
 * assignment only when the manager accepts it, through RozpisDay::place().
 */
class RozpisWeekAi extends Component
{
    /** Y-m-d of the first day of the week the builder is showing. */
    #[Locked]
    public string $weekStart;

    public function suggest(): void
    {
        $week = CarbonImmutable::parse($this->weekStart)->startOfDay();

        // The same gate as adding a slot: whoever may build the rozpis may ask for a draft of it.
        // Each placement is authorised again on accept, so this only guards the API spend.
        $this->authorize('create', [PositionSlot::class, $week]);

        $byDate = app(AiRozpisSuggestionService::class)->suggestWeek(app(Team::class), $week);

        if ($byDate === []) {
            $this->dispatch('toast', message: 'AI nenavrhla žiadne zaradenie.', type: 'error');

            return;
        }

        // Broadcast once; every day takes only its own date out of it.
        $this->dispatch('rozpis-week-suggested', byDate: $byDate);

        $this->dispatch(
            'toast',
            message: 'Návrh na celý týždeň: '.array_sum(array_map('count', $byDate)).' zaradení — potvrďte ich v dňoch.',
        );
    }

    #[Computed]
    public function enabled(): bool
    {
        return app(AiRozpisSuggestionService::class)->enabled()
            && (auth()->user()?->hasPermissionInTeam('assignment.assign-position', app(Team::class)) ?? false);
    }

    public function render()
    {
        return view('livewire.rozpis-week-ai');
    }
}
