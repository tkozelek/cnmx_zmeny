<?php

namespace App\Filament\Platform\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Účet')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Meno')->required()->maxLength(255),
                        TextInput::make('lastname')->label('Priezvisko')->required()->maxLength(255),
                        TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')->label('E-mail overený'),
                        Toggle::make('is_active')->label('Aktívny (môže sa prihlásiť)')
                            ->disabled(fn (?User $record): bool => (bool) $record?->is_super_admin)
                            ->helperText(fn (?User $record): ?string => $record?->is_super_admin ? 'Super admina najprv odober.' : null),
                    ]),
            ]);
    }
}
