<?php

namespace App\Livewire;

use App\Models\Position;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The cinema's position catalogue: create, rename, reorder, deactivate.
 *
 * Until this existed the only way to add a position was editing the seeder by hand, which is
 * why it is a prerequisite for the rozpis builder rather than a nice-to-have.
 */
class PositionList extends Component
{
    /** Null = the form is creating; set = the form is editing that position. */
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?string $color = null;

    public bool $isManager = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:80',
                // Scoped by hand: the model's global `team` scope does not reach the unique
                // rule's own query, and without the scope two cinemas could not both have a
                // "Bufet".
                Rule::unique('positions', 'name')
                    ->where('team_id', app(Team::class)->getKey())
                    ->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'isManager' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Názov pozície je povinný.',
            'name.unique' => 'Pozícia s týmto názvom už existuje.',
            'name.max' => 'Názov pozície môže mať najviac 80 znakov.',
            'code.max' => 'Skratka môže mať najviac 10 znakov.',
            'color.regex' => 'Farba musí byť v tvare #rrggbb.',
        ];
    }

    public function save(): void
    {
        $editing = $this->editingId ? $this->find($this->editingId) : null;

        $this->authorize($editing ? 'update' : 'create', $editing ?? Position::class);

        $data = $this->validate();

        $attributes = [
            'name' => $data['name'],
            'code' => $data['code'] ?: null,
            'color' => $data['color'] ?: null,
            'is_manager' => $data['isManager'],
        ];

        if ($editing) {
            $editing->update($attributes);
        } else {
            Position::create($attributes + ['sort_order' => $this->nextSortOrder()]);
        }

        $this->cancelEdit();
        unset($this->positions);

        $this->dispatch('toast', message: $editing ? 'Pozícia upravená.' : 'Pozícia pridaná.');
    }

    public function edit(int $id): void
    {
        $position = $this->find($id);

        $this->authorize('update', $position);

        $this->editingId = $position->id;
        $this->name = $position->name;
        $this->code = $position->code;
        $this->color = $position->color;
        $this->isManager = $position->is_manager;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'code', 'color', 'isManager']);
        $this->resetValidation();
    }

    /**
     * Deactivating is the destructive action the UI offers: `scopeSelectable()` already hides
     * inactive positions from new work, while deleting the row would cascade away every
     * historical assignment that used it (see the composite FK on `assignments`).
     */
    public function toggleActive(int $id): void
    {
        $position = $this->find($id);

        $this->authorize('update', $position);

        $position->update(['is_active' => ! $position->is_active]);

        unset($this->positions);

        $this->dispatch(
            'toast',
            message: $position->is_active ? 'Pozícia aktivovaná.' : 'Pozícia deaktivovaná.',
            type: $position->is_active ? 'success' : 'error',
        );
    }

    /**
     * Move one position up (-1) or down (+1).
     *
     * ponytail: re-indexes the whole list on every move instead of swapping two rows. A cinema
     * has a dozen positions, and seeded rows can share sort_order 0 — where a swap silently
     * does nothing. Swap just the two neighbours if this list ever grows to hundreds.
     */
    public function move(int $id, int $direction): void
    {
        $position = $this->find($id);

        $this->authorize('update', $position);

        $ordered = $this->positions->values();
        $from = $ordered->search(fn (Position $candidate): bool => $candidate->id === $position->id);
        $to = $from + $direction;

        if ($from === false || $to < 0 || $to >= $ordered->count()) {
            return;
        }

        $moved = $ordered->splice($from, 1)->first();
        $ordered->splice($to, 0, [$moved]);

        foreach ($ordered as $index => $item) {
            $item->update(['sort_order' => ($index + 1) * 10]);
        }

        unset($this->positions);
    }

    /**
     * Inactive positions stay listed — they are the whole point of "deactivated, not deleted",
     * and they need a way back.
     *
     * @return Collection<int, Position>
     */
    #[Computed]
    public function positions(): Collection
    {
        return Position::orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()?->can('create', Position::class) ?? false;
    }

    public function render()
    {
        return view('livewire.position-list');
    }

    private function find(int $id): Position
    {
        return Position::findOrFail($id);
    }

    private function nextSortOrder(): int
    {
        // Tens, so a manual insert between two positions has room.
        return ((int) Position::max('sort_order')) + 10;
    }
}
