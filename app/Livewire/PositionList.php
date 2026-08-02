<?php

namespace App\Livewire;

use App\Models\Position;
use App\Models\PositionGroup;
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

    /** Which group the position being edited is filed under. Null = ungrouped, which is allowed. */
    public ?int $groupId = null;

    /** The create/rename-group field, kept apart from the position form above. */
    public string $groupName = '';

    #[Locked]
    public ?int $editingGroupId = null;

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
            // Scoped like the unique rule above, so a crafted request cannot file this cinema's
            // position under another cinema's group.
            'groupId' => [
                'nullable', 'integer',
                Rule::exists('position_groups', 'id')->where('team_id', app(Team::class)->getKey()),
            ],
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
            'groupId.exists' => 'Táto skupina neexistuje.',
            'groupName.required' => 'Názov skupiny je povinný.',
            'groupName.unique' => 'Skupina s týmto názvom už existuje.',
            'groupName.max' => 'Názov skupiny môže mať najviac 80 znakov.',
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
            'position_group_id' => $data['groupId'] ?: null,
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
        $this->groupId = $position->position_group_id;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'code', 'color', 'isManager', 'groupId']);
        $this->resetValidation();
    }

    /**
     * Create a group, or rename the one being edited.
     *
     * Every group action is gated on `create` for a Position rather than a policy of its own: a
     * group is a heading over the catalogue, so whoever may add to the catalogue may organise it.
     * `update`/`delete` on PositionPolicy both need a Position instance, which a group is not.
     */
    public function saveGroup(): void
    {
        $this->authorize('create', Position::class);

        $data = $this->validate([
            'groupName' => [
                'required', 'string', 'max:80',
                Rule::unique('position_groups', 'name')
                    ->where('team_id', app(Team::class)->getKey())
                    ->ignore($this->editingGroupId),
            ],
        ]);

        if ($this->editingGroupId) {
            $this->findGroup($this->editingGroupId)->update(['name' => $data['groupName']]);
        } else {
            PositionGroup::create([
                'name' => $data['groupName'],
                // Tens, so a group can later be slotted between two others.
                'sort_order' => ((int) PositionGroup::max('sort_order')) + 10,
            ]);
        }

        $renamed = (bool) $this->editingGroupId;

        $this->cancelGroupEdit();
        unset($this->groups, $this->positions);

        $this->dispatch('toast', message: $renamed ? 'Skupina premenovaná.' : 'Skupina pridaná.');
    }

    public function editGroup(int $id): void
    {
        $group = $this->findGroup($id);

        $this->authorize('create', Position::class);

        $this->editingGroupId = $group->id;
        $this->groupName = $group->name;
    }

    public function cancelGroupEdit(): void
    {
        $this->reset(['editingGroupId', 'groupName']);
        $this->resetValidation();
    }

    /**
     * Delete a group. Its positions survive, unfiled.
     *
     * Safe to delete outright, unlike a position: nothing historical points at a group, so there
     * is no assignment to cascade away. The FK nulls the column on MySQL; it is nulled here too
     * because SQLite could not be given that constraint after the fact.
     */
    public function deleteGroup(int $id): void
    {
        $group = $this->findGroup($id);

        $this->authorize('create', Position::class);

        Position::where('position_group_id', $group->getKey())->update(['position_group_id' => null]);

        $group->delete();

        $this->cancelGroupEdit();
        unset($this->groups, $this->positions);

        $this->dispatch('toast', message: 'Skupina zmazaná — pozície ostali zachované.', type: 'error');
    }

    /**
     * Move a group up (-1) or down (+1). Re-indexes the list, for the reason move() explains.
     */
    public function moveGroup(int $id, int $direction): void
    {
        $this->authorize('create', Position::class);

        $ordered = $this->groups->values();
        $from = $ordered->search(fn (PositionGroup $candidate): bool => $candidate->id === $id);

        if ($from === false) {
            return;
        }

        $to = $from + $direction;

        if ($to < 0 || $to >= $ordered->count()) {
            return;
        }

        $moved = $ordered->splice($from, 1)->first();
        $ordered->splice($to, 0, [$moved]);

        foreach ($ordered as $index => $item) {
            $item->update(['sort_order' => ($index + 1) * 10]);
        }

        unset($this->groups, $this->positions);
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
        // Ordered the way the rozpis prints them: by group first, ungrouped last. Sorted in PHP
        // rather than by a join, because that is where groupOrder() already lives.
        return Position::with('group')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Position $position): array => [
                $position->groupOrder(),
                $position->groupName() ?? '',
                $position->sort_order,
                $position->name,
            ])
            ->values();
    }

    /**
     * @return Collection<int, PositionGroup>
     */
    #[Computed]
    public function groups(): Collection
    {
        return PositionGroup::ordered()->withCount('positions')->get();
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

    /** Team-scoped by the model's global scope, so another cinema's group is simply not found. */
    private function findGroup(int $id): PositionGroup
    {
        return PositionGroup::findOrFail($id);
    }

    private function nextSortOrder(): int
    {
        // Tens, so a manual insert between two positions has room.
        return ((int) Position::max('sort_order')) + 10;
    }
}
