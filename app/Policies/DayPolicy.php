<?php

namespace App\Policies;

use App\Models\Day;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DayPolicy
{
    public function toggleUser(User $user, Day $day)
    {
        if ($user->id_role == config('constants.roles.zablokovany')) {
            return Response::deny('User is locked.', 401);
        }

        if ($day->week->locked) {
            return Response::deny('Week is locked.', 403);
        }

        return Response::allow();
    }

    public function removeUser(User $currentUser, Day $day): bool
    {
        return $currentUser->isAdmin();
    }
}
