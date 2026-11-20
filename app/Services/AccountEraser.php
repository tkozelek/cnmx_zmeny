<?php

namespace App\Services;

use App\Models\BugReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a person for good - the retention prune and a manager's "Vymazať natrvalo".
 *
 * The account row cascades to sign-ups, absences and memberships. What has no foreign key to
 * cascade through goes explicitly: Spatie's role/permission links in every team (with teams on,
 * its own delete hook only detaches the current team's), reset and invite tokens, sessions,
 * and bug reports.
 */
class AccountEraser
{
    public function erase(User $user): void
    {
        DB::transaction(function () use ($user): void {
            foreach (['model_has_roles', 'model_has_permissions'] as $table) {
                DB::table($table)
                    ->where('model_type', $user->getMorphClass())
                    ->where('model_id', $user->id)
                    ->delete();
            }

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table(config('auth.passwords.add_user.table'))->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // Via the model so the deleted hook removes the files.
            BugReport::query()->where('user_id', $user->id)->get()->each->delete();

            $user->delete();
        });
    }
}
