<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayService
{
    public function getAllActive()
    {
        return Holiday::with('user')->where('date_to', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('date_canceled')
            ->orderBy('date_to', 'desc')
            ->get();
    }

    public function getAllInactive()
    {
        return Holiday::with('user')->where('date_to', '<', Carbon::now()->format('Y-m-d'))
            ->orWhereNotNull('date_canceled')
            ->orderBy('date_to', 'desc')
            ->paginate(15);
    }
}
