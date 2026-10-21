<?php

namespace App\Filament\Platform\Resources\Users\Tables;

use App\Models\Team;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('lastname')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('teams'))
            ->columns([
                TextColumn::make('lastname')->label('Priezvisko')->searchable()->sortable(),
                TextColumn::make('name')->label('Meno')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('teams.name')->label('Kiná')->badge()->limitList(3),
                IconColumn::make('is_super_admin')->label('Super admin')->boolean()
                    ->trueIcon('heroicon-o-shield-check')->falseIcon(''),
                IconColumn::make('is_active')->label('Aktívny')->boolean(),
                IconColumn::make('email_verified_at')->label('Overený')->boolean()
                    ->state(fn (User $record): bool => $record->email_verified_at !== null),
                TextColumn::make('last_login_at')->label('Posledné prihlásenie')->since()->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_super_admin')->label('Super admin'),
                TernaryFilter::make('is_active')->label('Aktívny'),
                SelectFilter::make('team')
                    ->label('Kino')
                    ->options(fn (): array => Team::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('teams', fn (Builder $q) => $q->whereKey($data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
