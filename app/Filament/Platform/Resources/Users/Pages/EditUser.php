<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Filament\Platform\Resources\Users\UserResource;
use App\Models\User;
use App\Services\PlatformAudit;
use App\Services\SuperAdminRoster;
use App\Services\SuperAdminSession;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->guardedAction('grantSuperAdmin')
                ->label('Udeliť super admina')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('warning')
                ->visible(fn (User $record): bool => ! $record->is_super_admin)
                ->modalDescription('Získa plný prístup ku všetkým kinám a k tejto administrácii.')
                ->action(fn (User $record) => $this->change('super-admin.granted', fn () => $this->roster()->grant($record))),

            $this->guardedAction('revokeSuperAdmin')
                ->label('Odobrať super admina')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color('danger')
                ->visible(fn (User $record): bool => $record->is_super_admin)
                ->action(fn (User $record) => $this->change('super-admin.revoked', fn () => $this->roster()->revoke($record, Filament::auth()->user()))),

            $this->guardedAction('resetMultiFactor')
                ->label('Resetovať 2FA')
                ->icon(Heroicon::OutlinedKey)
                ->visible(fn (User $record): bool => filled($record->getAppAuthenticationSecret()) && ! $record->is(Filament::auth()->user()))
                ->modalDescription('Pri ďalšom prihlásení si nastaví novú aplikáciu na overovanie.')
                ->action(fn (User $record) => $this->change('super-admin.mfa-reset', fn () => $this->roster()->resetMultiFactor($record))),

            DeleteAction::make()
                ->label('Vymazať účet')
                ->hidden(fn (User $record): bool => $record->is_super_admin)
                ->modalDescription('Vymaže účet aj so všetkými jeho zápismi a absenciami vo všetkých kinách.')
                ->schema([
                    TextInput::make('confirm_email')
                        ->label(fn (User $record): string => "Pre potvrdenie napíš {$record->email}")
                        ->required()
                        ->in(fn (User $record): array => [$record->email]),
                ]),
        ];
    }

    /** Anything that changes who holds platform-wide power asks for the acting admin's password first. */
    private function guardedAction(string $name): Action
    {
        return Action::make($name)
            ->requiresConfirmation()
            ->schema([
                TextInput::make('current_password')
                    ->label('Tvoje heslo')
                    ->password()
                    ->required()
                    ->currentPassword(guard: SuperAdminSession::GUARD),
            ]);
    }

    private function change(string $event, callable $change): void
    {
        try {
            $change();
        } catch (DomainException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        app(PlatformAudit::class)->record($event, $this->getRecord());
        $this->refreshFormData(['is_active']);
        Notification::make()->success()->title('Uložené.')->send();
    }

    private function roster(): SuperAdminRoster
    {
        return app(SuperAdminRoster::class);
    }
}
