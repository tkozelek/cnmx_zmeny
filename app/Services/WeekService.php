<?php

namespace App\Services;

use App\Models\Day;
use App\Models\Week;
use Carbon\Carbon;

class WeekService
{
    public function generateWeek() //	date_from	date_to	locked	next_week_id	prev_week_id
    {$day = Carbon::now()->modify('next thursday');

        $week = new Week;
        $week->date_from = $day;
        $week->date_to = $day->copy()->addWeek()->addDays(-1);
        $week->save();

        for ($i = 0; $i < 7; $i++) { // id	text	date	id_week
            $new_day = new Day;
            $new_day->date = $day->copy()->addDays($i);
            $new_day->id_week = $week->id;
            $new_day->save();
        }

        return $week;
    }

    public function checkAndGenerateWeeks($num_of_weeks)
    {
        $currentWeek = $this->getCurrentWeek(); // aktualny
        $date_to = $currentWeek->date_to;
        for ($i = 0; $i < $num_of_weeks; $i++) { // 4 dopredu

            if ($currentWeek->next_week_id) {
                $currentWeek = Week::find($currentWeek->next_week_id);
                $date_to = $currentWeek->date_to;

                continue;
            }

            $newWeek = new Week;
            $newWeek->date_from = $date_to->copy()->addDay();
            $newWeek->date_to = $date_to->copy()->addWeek();
            $newWeek->locked = 0;
            $newWeek->prev_week_id = $currentWeek->id;
            $newWeek->save();

            $currentWeek->next_week_id = $newWeek->id;
            $currentWeek->save();

            for ($j = 0; $j < 7; $j++) { // 7 dni v tyzdni
                $day = new Day;
                $day->date = $newWeek->date_from->copy()->addDays($j);
                $day->id_week = $newWeek->id;
                $day->save();
            }
            $currentWeek = $newWeek;
            $date_to = $newWeek->date_to;
        }
    }

    public function getActiveWeek()
    {
        $currentDay = Carbon::now()->addWeek()->format('Y-m-d');
        $week = Week::where('date_from', '<=', $currentDay)
            ->where('locked', 0)
            ->first();

        if ($week) {
            return $week;
        }

        $weekif = Week::orderBy('locked', 'asc')
            ->orderBy('date_from', 'asc')
            ->first();

        if ($weekif) {
            return $weekif;
        }

        $weekModel = Week::oldest('id')->first();

        if ($weekModel) {
            return $weekModel;
        }

        return $this->generateWeek();
    }

    public function getCurrentWeek()
    {
        $currentDay = Carbon::now()->format('Y-m-d');
        $week = Week::where('date_from', '<=', $currentDay)
            ->where('date_to', '>=', $currentDay)
            ->first();

        if ($week) {
            return $week;
        }

        $weekModel = Week::oldest('id')->first();

        if ($weekModel) {
            return $weekModel;
        }

        return $this->generateWeek();
    }
}
