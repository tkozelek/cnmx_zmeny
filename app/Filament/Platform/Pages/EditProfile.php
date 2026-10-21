<?php

namespace App\Filament\Platform\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/** E-mail changes stay with the app's own verified flow, so the panel profile does not offer them. */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Meno')->required()->maxLength(255),
            TextInput::make('lastname')->label('Priezvisko')->required()->maxLength(255),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }
}
