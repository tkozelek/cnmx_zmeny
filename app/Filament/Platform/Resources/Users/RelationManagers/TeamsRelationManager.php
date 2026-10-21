<?php

namespace App\Filament\Platform\Resources\Users\RelationManagers;

use App\Enums\Role;
use App\Filament\Platform\Concerns\ManagesMemberships;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsRelationManager extends RelationManager
{
    use ManagesMemberships;

    protected static string $relationship = 'teams';

    protected static ?string $title = 'Členstvá v kinách';

    protected static ?string $modelLabel = 'členstvo';

    protected static bool $shouldSkipAuthorization = true;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Kino'),
                TextColumn::make('role')
                    ->label('Rola')
                    ->badge()
                    ->state(fn (Team $record): string => $this->membership()->roleOf($this->user(), $record)?->label() ?? '—'),
                TextColumn::make('approved_at')
                    ->label('Schválený')
                    ->state(fn (Team $record) => $record->pivot->approved_at)
                    ->date('d.m.Y')
                    ->placeholder('čaká'),
            ])
            ->headerActions([
                Action::make('addToTeam')
                    ->label('Pridať do kina')
                    ->hidden(fn (): bool => $this->user()->is_super_admin)
                    ->schema([
                        Select::make('team_id')
                            ->label('Kino')
                            ->options(fn (): array => Team::query()
                                ->whereDoesntHave('users', fn ($q) => $q->whereKey($this->user()->getKey()))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required(),
                        $this->roleSelect(),
                        $this->headManagerPassword(),
                    ])
                    ->action(fn (array $data) => $this->membership()->add($this->user(), Team::findOrFail($data['team_id']), Role::from($data['role']))),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Schváliť')
                    ->color('success')
                    ->visible(fn (Team $record): bool => $record->pivot->approved_at === null)
                    ->requiresConfirmation()
                    ->action(fn (Team $record) => $this->membership()->approve($this->user(), $record)),
                Action::make('changeRole')
                    ->label('Zmeniť rolu')
                    ->fillForm(fn (Team $record): array => ['role' => $this->membership()->roleOf($this->user(), $record)?->value])
                    ->schema([$this->roleSelect(), $this->headManagerPassword()])
                    ->action(fn (Team $record, array $data) => $this->attempt(fn () => $this->membership()->changeRole($this->user(), $record, Role::from($data['role'])))),
                Action::make('remove')
                    ->label('Odobrať z kina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Team $record) => $this->attempt(fn () => $this->membership()->remove($this->user(), $record))),
            ]);
    }

    private function user(): User
    {
        return $this->getOwnerRecord();
    }
}
