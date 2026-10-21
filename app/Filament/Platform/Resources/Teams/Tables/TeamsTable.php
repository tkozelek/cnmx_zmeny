<?php

namespace App\Filament\Platform\Resources\Teams\Tables;

use App\Enums\TeamFeature;
use App\Models\Team;
use App\Services\SuperAdminSession;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Livewire\Component;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount(['approvedUsers', 'pendingUsers']))
            ->columns([
                TextColumn::make('name')->label('Kino')->searchable()->sortable(),
                TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Registrácia')->boolean(),
                TextColumn::make('approved_users_count')->label('Členovia')->sortable(),
                TextColumn::make('pending_users_count')->label('Čakajú')->sortable()
                    ->badge()->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('modules')
                    ->label('Moduly')
                    ->badge()
                    ->state(fn (Team $record): array => collect(TeamFeature::cases())
                        ->filter(fn (TeamFeature $feature): bool => $record->hasFeature($feature))
                        ->map(fn (TeamFeature $feature): string => $feature->label())
                        ->values()->all()),
                TextColumn::make('created_at')->label('Vytvorené')->date('d.m.Y')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Ponúka sa pri registrácii'),
            ])
            ->recordActions([
                Action::make('enter')
                    ->label('Otvoriť kino')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->action(function (Team $record, Component $livewire): void {
                        app(SuperAdminSession::class)->enter(Filament::auth()->user(), $record);
                        $livewire->redirect(route('calendar.index'));
                    }),
                EditAction::make(),
            ]);
    }
}
