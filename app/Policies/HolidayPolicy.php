<?php

namespace App\Policies;

use App\Models\Holiday;

class HolidayPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function delete(Holiday $holiday)
    {
        $user = auth()->user();
        if ($holiday->id_user != $user->id && ! $user->hasRole(config('constants.roles.admin'))) {
            return false;
        }
        if ((now() <= $holiday->date_to->addWeek() || now() <= $holiday->date_canceled->addWeek())
            && ! $user->hasRole(config('constants.roles.admin'))) {
            return false;
        }

        return true;
    }
}
