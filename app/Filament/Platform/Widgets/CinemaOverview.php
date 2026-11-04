<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Team;
use App\Support\SlovakDate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CinemaOverview extends TableWidget
{
    /** Days the sign-up average is taken over - four whole weeks, so every weekday counts equally. */
    private const int AVERAGE_DAYS = 28;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Kiná v číslach';

    public function table(Table $table): Table
    {
        $today = today();

        return $table
            ->query(fn () => Team::query()
                ->withCount('approvedUsers as members_count')
                ->addSelect([
                    'active_members' => $this->perTeam('team_user')
                        ->join('users', 'users.id', '=', 'team_user.user_id')
                        ->whereNotNull('team_user.approved_at')
                        ->where('users.last_login_at', '>=', now()->subDays(30))
                        ->selectRaw('count(*)'),
                    'recent_signups' => $this->perTeam('assignments')
                        ->whereBetween('date', [$today->copy()->subDays(self::AVERAGE_DAYS - 1)->toDateString(), $today->toDateString()])
                        ->selectRaw('count(*)'),
                    'upcoming_signups' => $this->perTeam('assignments')
                        ->whereBetween('date', [$today->copy()->addDay()->toDateString(), $today->copy()->addDays(7)->toDateString()])
                        ->selectRaw('count(*)'),
                    'absent_today' => $this->perTeam('absences')
                        ->where('status', 'active')
                        ->where('date_from', '<=', $today->toDateString())
                        ->where('date_to', '>=', $today->toDateString())
                        ->selectRaw('count(distinct user_id)'),
                    'last_signup_at' => $this->perTeam('assignments')->selectRaw('max(created_at)'),
                ]))
            ->defaultSort('recent_signups', 'desc')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Kino')->searchable()->sortable(),
                TextColumn::make('members_count')->label('Členovia')->numeric()->sortable(),
                TextColumn::make('active_members')->label('Aktívni (30 dní)')->numeric()->sortable(),
                TextColumn::make('recent_signups')
                    ->label('Ø zápisov / deň')
                    ->tooltip('Priemer za posledných '.self::AVERAGE_DAYS.' dní')
                    ->formatStateUsing(fn (mixed $state): string => number_format((int) $state / self::AVERAGE_DAYS, 1, ',', ' '))
                    ->sortable(),
                TextColumn::make('upcoming_signups')->label('Zápisy na 7 dní')->numeric()->sortable(),
                TextColumn::make('absent_today')->label('Dnes neprítomní')->numeric()->sortable(),
                TextColumn::make('last_signup_at')
                    ->label('Posledný zápis')
                    ->formatStateUsing(fn (?string $state): string => $state ? SlovakDate::dateTime($state) : '—')
                    ->placeholder('—')
                    ->sortable(),
            ]);
    }

    private function perTeam(string $table): Builder
    {
        return DB::table($table)->whereColumn("{$table}.team_id", 'teams.id');
    }
}
