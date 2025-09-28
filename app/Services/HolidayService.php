<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;

class HolidayService
{
    public function getAllActive(?User $user = null)
    {
        return Holiday::with('user')->where('date_to', '>=', Carbon::now()->format('Y-m-d'))
            ->when($user, function ($query) use ($user) {
                return $query->where('id_user', $user->id);
            })
            ->whereNull('date_canceled')
            ->orderBy('date_to', 'asc')
            ->get();
    }

    public function getAllInactive(?User $user = null)
    {
        return Holiday::with('user')
            ->when($user, function ($query) use ($user) {
                $query->where('id_user', $user->id);
            })
            ->where(function ($query) {
                $query->where('date_to', '<', Carbon::now()->format('Y-m-d'))
                    ->orWhereNotNull('date_canceled');
            })
            ->orderBy('date_to', 'desc')
            ->paginate(15);
    }
}
