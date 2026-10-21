<?php

namespace App\Filament\Platform\Resources\Teams\Schemas;

use App\Enums\TeamFeature;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Rules\WeekStartDayChangeable;
use App\Services\PlatformAudit;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Pennant\Feature;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Kino')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Názov')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state ?? '')) : null),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label('Ponúka sa pri registrácii')
                            ->default(true),
                    ]),

                Section::make('Nastavenia')
                    ->relationship('settings')
                    ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => [
                        ...$data,
                        'fairness_day_weights' => $data['fairness_day_weights'] ?? TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS,
                        'holiday_weight' => $data['holiday_weight'] ?? TeamSetting::DEFAULT_HOLIDAY_WEIGHT,
                        'quick_times' => $data['quick_times'] ?? TeamSetting::DEFAULT_QUICK_TIMES,
                    ])
                    ->visibleOn('edit')
                    ->columns(2)
                    ->schema([
                        Select::make('week_start_day')
                            ->label('Začiatok týždňa')
                            ->options(self::weekDays())
                            ->required()
                            ->rule(fn (?TeamSetting $record) => $record ? new WeekStartDayChangeable($record->team) : null),
                        TextInput::make('week_lookahead')
                            ->label('Týždňov dopredu na zápis')
                            ->integer()->minValue(1)->maxValue(52)->required(),
                        TextInput::make('absence_deadline_days')
                            ->label('Uzávierka absencií (dni vopred)')
                            ->integer()->minValue(0)->maxValue(30)->required(),
                        TextInput::make('stale_absence_deletion_days')
                            ->label('Vymazanie neaktívnej absencie po (dňoch, 0 = hneď)')
                            ->integer()->minValue(0)->maxValue(365)->required(),
                        TextInput::make('timezone')
                            ->label('Časové pásmo')
                            ->required()
                            ->rule(Rule::in(timezone_identifiers_list())),
                        TextInput::make('locale')
                            ->label('Jazyk')
                            ->required()
                            ->maxLength(10),
                        Fieldset::make('Rozpis')
                            ->visible(fn (EditRecord $livewire): bool => $livewire->getRecord()->hasFeature(TeamFeature::Rozpis))
                            ->columns(7)
                            ->schema([
                                ...collect(self::weekDays())->map(fn (string $day, int $i): TextInput => TextInput::make("fairness_day_weights.{$i}")
                                    ->label($day)
                                    ->numeric()->minValue(0.1)->maxValue(10)->required()
                                    ->dehydrateStateUsing(fn ($state): float => (float) $state))->values()->all(),
                                TextInput::make('fairness_window_weeks')->label('Okno spravodlivosti (týždne)')
                                    ->integer()->minValue(1)->maxValue(52)->required()->columnSpan(3),
                                TextInput::make('holiday_weight')->label('Váha sviatku')
                                    ->numeric()->minValue(0.1)->maxValue(10)->required()->columnSpan(2)
                                    ->dehydrateStateUsing(fn ($state): float => (float) $state),
                                TagsInput::make('quick_times')->label('Rýchle časy nástupu')
                                    ->placeholder('HH:MM')
                                    ->nestedRecursiveRules(['date_format:H:i'])
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Moduly')
                    ->description('Vypnutý modul kino vôbec nevidí – jeho stránky vracajú 404.')
                    ->visibleOn('edit')
                    ->schema(collect(TeamFeature::cases())->map(self::featureToggle(...))->all()),
            ]);
    }

    private static function featureToggle(TeamFeature $feature): Toggle
    {
        $required = $feature->requires();

        return Toggle::make("features.{$feature->name}")
            ->label($feature->label())
            ->helperText($feature->description())
            ->live()
            ->dehydrated(false)
            ->disabled(fn (Get $get): bool => $required !== null && ! $get("features.{$required->name}"))
            ->afterStateHydrated(fn (Toggle $component, Team $record) => $component->state(Feature::for($record)->active($feature)))
            ->saveRelationshipsUsing(function (Team $record, ?bool $state) use ($feature): void {
                if ((bool) $state === Feature::for($record)->active($feature)) {
                    return;
                }

                $state ? Feature::for($record)->activate($feature) : Feature::for($record)->deactivate($feature);

                app(PlatformAudit::class)->record($state ? 'feature.activated' : 'feature.deactivated', $record, ['feature' => $feature->value]);
            });
    }

    /** @return array<int, string> */
    private static function weekDays(): array
    {
        $monday = CarbonImmutable::now()->startOfWeek()->locale('sk');

        return collect(range(0, 6))->mapWithKeys(fn (int $i): array => [$i => Str::ucfirst($monday->addDays($i)->dayName)])->all();
    }
}
