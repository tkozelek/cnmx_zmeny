<?php

namespace App\Filament\Platform;

use App\Models\User;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/** Filament's default fetches ui-avatars.com - blocked by our CSP, and a third party the privacy page doesn't list. */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = $record instanceof User
            ? mb_strtoupper(mb_substr($record->name, 0, 1).mb_substr($record->lastname, 0, 1))
            : '?';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#0369a1"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="26" fill="#fff">'
            .e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
