<?php

namespace App\Livewire;

use App\Enums\Role as RoleEnum;
use App\Models\Team;
use App\Models\User;
use App\Notifications\UserAllowedToLogin;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Spatie\Permission\Models\Role;

class UsersDataTable extends DataTableComponent
{
    protected $model = User::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTheme('tailwind')
            ->setDefaultSort('lastname', 'asc')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setColumnSelectStatus(true)
            ->setFilterPillsStatus(true)
            ->setSearchPlaceholder('Vyhľadať používateľa...')
            ->setEmptyMessage('Žiadni používatelia neboli nájdení.');
    }

    /**
     * The team's people, once they have confirmed their address.
     *
     * Registration is two gates - confirm the e-mail, then be approved by a manager - and only
     * the second one is a decision anybody makes here. Somebody who has not passed the first is
     * not yet a candidate, so they stay out of the queue rather than sitting in it as a row
     * nobody is allowed to accept.
     */
    public function builder(): Builder
    {
        $team = app(Team::class);

        return User::query()
            ->select('users.*')
            ->whereNotNull('users.email_verified_at')
            ->whereHas('teams', fn (Builder $q) => $q->where('teams.id', $team->id))
            ->with(['roles', 'teams']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Rola / Stav')
                ->options([
                    '' => 'Všetky role',
                    RoleEnum::Employee->value => RoleEnum::Employee->label(),
                    RoleEnum::Manager->value => RoleEnum::Manager->label(),
                    RoleEnum::HeadManager->value => RoleEnum::HeadManager->label(),
                    'pending' => 'Čaká na schválenie',
                    'blocked' => 'Zablokovaný',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $team = app(Team::class);

                    match ($value) {
                        RoleEnum::Employee->value,
                        RoleEnum::Manager->value,
                        RoleEnum::HeadManager->value => $builder->role($value),
                        'pending' => $builder->whereHas('teams', fn ($q) => $q->where('teams.id', $team->id)->whereNull('team_user.approved_at')),
                        'blocked' => $builder->where('users.is_active', false),
                        default => null,
                    };
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Meno a Priezvisko', 'lastname')
                ->sortable()
                ->searchable(fn (Builder $query, string $term) => $query->whereAny(['name', 'lastname'], 'LIKE', "%{$term}%"))
                ->format(function ($value, $row) {
                    $profileUrl = route('profile.show', ['user' => $row->id]);
                    $fullName = trim(($row->name ?? '').' '.($row->lastname ?? ''));

                    return '<a href="'.$profileUrl.'" class="font-bold text-white hover:text-sky-400 transition">'.e($fullName ?: $row->email).'</a>';
                })
                ->html(),

            Column::make('E-mail', 'email')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => '<span class="text-neutral-300">'.$value.'</span>')
                ->html(),

            Column::make('Rola', 'id')
                ->sortable(function (Builder $query, string $direction) {
                    return $query->orderBy(
                        Role::select('name')
                            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereColumn('model_has_roles.model_id', 'users.id')
                            ->limit(1),
                        $direction
                    );
                })
                ->format(function ($value, $row) {
                    $team = app(Team::class);
                    $membership = $row->teams->firstWhere('id', $team->id);
                    $isPending = is_null($membership?->pivot?->approved_at);
                    $roleName = $row->roles->first()?->name;

                    // Blocked first: a denied member is both inactive *and* unapproved, and
                    // "zablokovaný" is the state that actually explains why they cannot get in.
                    if (! $row->is_active) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-neutral-800 text-neutral-400 border border-neutral-700">Zablokovaný</span>';
                    }

                    if ($isPending) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30">Čaká na schválenie</span>';
                    }

                    if ($roleName === RoleEnum::HeadManager->value) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30">'.RoleEnum::HeadManager->label().'</span>';
                    }

                    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30">'.(RoleEnum::tryFrom($roleName)?->label() ?? '-').'</span>';

                })
                ->html(),

            Column::make('Posledná aktivita', 'updated_at')
                ->sortable()
                ->format(fn ($value) => '<span class="text-neutral-400">'.$value?->format('d.m.Y H:i').'</span>')
                ->html(),

            Column::make('Akcie', 'id')
                ->format(function ($value, $row) {
                    $team = app(Team::class);
                    $membership = $row->teams->firstWhere('id', $team->id);
                    $isPending = is_null($membership?->pivot?->approved_at);

                    // A denied member keeps a null `approved_at`, so "still waiting" is the
                    // active ones - otherwise a blocked account would be offered for approval.
                    if ($isPending && $row->is_active) {
                        return '<div class="flex items-center justify-start gap-2">
                            <button wire:click="accept('.$row->id.')" title="Schváliť" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-sm transition"><i class="fa-solid fa-check text-sm"></i></button>
                            <button wire:click="deny('.$row->id.')" title="Zamietnuť" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-sm transition"><i class="fa-solid fa-xmark text-sm"></i></button>
                        </div>';
                    }

                    $editUrl = route('admin.users.edit', ['user' => $row->id]);

                    return '<div class="flex items-center justify-start">
                        <a href="'.$editUrl.'" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-100 px-3.5 py-2 text-xs font-semibold shadow-sm transition"><i class="fa-solid fa-pen-to-square text-xs text-neutral-400"></i> Upraviť</a>
                    </div>';
                })
                ->html(),
        ];
    }

    public function accept(User $user): void
    {
        $this->authorize('approve', $user);

        if (! $user->hasVerifiedEmail()) {
            $this->dispatch('toast', message: 'Používateľ si ešte neoveril e-mail.', type: 'error');

            return;
        }

        $team = app(Team::class);
        $team->users()->updateExistingPivot($user->id, ['approved_at' => now()]);
        $user->forgetApprovedTeamsCache();

        $user->notify(new UserAllowedToLogin($user));
        $this->dispatch('toast', message: 'Používateľ schválený.');
    }

    /**
     * Refuse a pending membership.
     *
     * `approved_at` stays null - it used to be stamped here, which left a denied person
     * *approved* in the pivot with only `is_active` keeping them out. Since `is_active` is
     * editable from the user edit form, re-enabling a denied account silently granted them a
     * membership nobody ever approved.
     *
     * Blocking the account is global (`users.is_active` is not team-scoped), so it is only the
     * right answer when this cinema was their only one - otherwise refusing them here would lock
     * them out of a cinema that did approve them.
     */
    public function deny(User $user): void
    {
        $this->authorize('approve', $user);

        $team = app(Team::class);
        $team->users()->updateExistingPivot($user->id, ['approved_at' => null]);

        if ($user->teams()->count() === 1) {
            $user->update(['is_active' => false]);
        }

        $user->forgetApprovedTeamsCache();

        $this->dispatch('toast', message: 'Používateľ zamietnutý.');
    }
}
