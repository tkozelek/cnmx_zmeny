<?php

namespace App\Filament\Platform\Resources\Teams\RelationManagers;

use App\Enums\Role;
use App\Filament\Platform\Concerns\ManagesMemberships;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class MembersRelationManager extends RelationManager
{
    use ManagesMemberships;

    protected static string $relationship = 'users';

    protected static ?string $title = 'Členovia';

    protected static ?string $modelLabel = 'člen';

    protected static ?string $pluralModelLabel = 'členovia';

    protected static bool $shouldSkipAuthorization = true;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->select('users.*')->addSelect(['team_role' => $this->roleSubquery()]))
            ->defaultSort('lastname')
            ->columns([
                TextColumn::make('lastname')->label('Priezvisko')->searchable()->sortable(),
                TextColumn::make('name')->label('Meno')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('team_role')
                    ->label('Rola')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Role::tryFrom($state ?? '')?->label() ?? '—'),
                TextColumn::make('approved_at')
                    ->label('Schválený')
                    ->state(fn (User $record) => $record->pivot->approved_at)
                    ->date('d.m.Y')
                    ->placeholder('čaká'),
                IconColumn::make('is_active')->label('Aktívny')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('approved')
                    ->label('Schválení')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('team_user.approved_at'),
                        false: fn (Builder $query) => $query->whereNull('team_user.approved_at'),
                    ),
            ])
            ->headerActions([
                Action::make('addMember')
                    ->label('Pridať člena')
                    ->schema([
                        Select::make('user_id')
                            ->label('Používateľ')
                            ->searchable()
                            ->required()
                            ->getSearchResultsUsing(fn (string $search): array => User::query()
                                ->where('is_super_admin', false)
                                ->whereDoesntHave('teams', fn (Builder $q) => $q->whereKey($this->team()->getKey()))
                                ->where(fn (Builder $q) => $q->where('email', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [$user->getKey() => "{$user->lastname} {$user->name} ({$user->email})"])
                                ->all()),
                        $this->roleSelect(),
                        $this->headManagerPassword(),
                    ])
                    ->action(fn (array $data) => $this->membership()->add(User::findOrFail($data['user_id']), $this->team(), Role::from($data['role']))),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Schváliť')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->pivot->approved_at === null)
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $this->membership()->approve($record, $this->team())),
                Action::make('changeRole')
                    ->label('Zmeniť rolu')
                    ->fillForm(fn (User $record): array => ['role' => $record->team_role])
                    ->schema([$this->roleSelect(), $this->headManagerPassword()])
                    ->action(fn (User $record, array $data) => $this->attempt(fn () => $this->membership()->changeRole($record, $this->team(), Role::from($data['role'])))),
                Action::make('remove')
                    ->label('Odobrať z kina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Účet zostane, len stratí prístup do tohto kina a svoju rolu v ňom.')
                    ->action(fn (User $record) => $this->attempt(fn () => $this->membership()->remove($record, $this->team()))),
            ]);
    }

    private function roleSubquery(): QueryBuilder
    {
        $modelHasRoles = config('permission.table_names.model_has_roles');

        return DB::table($modelHasRoles)
            ->join(config('permission.table_names.roles').' as roles', 'roles.id', '=', "{$modelHasRoles}.role_id")
            ->whereColumn("{$modelHasRoles}.model_id", 'users.id')
            ->where("{$modelHasRoles}.model_type", (new User)->getMorphClass())
            ->where("{$modelHasRoles}.team_id", $this->team()->getKey())
            ->select('roles.name')
            ->limit(1);
    }

    private function team(): Team
    {
        return $this->getOwnerRecord();
    }
}
