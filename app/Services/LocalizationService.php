<?php

namespace App\Services;

class LocalizationService
{
    public function mapEngToSvk($days)
    {
        $dayTranslations = [
            'Monday' => 'Pondelok',
            'Tuesday' => 'Utorok',
            'Wednesday' => 'Streda',
            'Thursday' => 'Štvrtok',
            'Friday' => 'Piatok',
            'Saturday' => 'Sobota',
            'Sunday' => 'Nedeľa',
        ];

        return $days->map(function ($day) use ($dayTranslations) {
            $englishDay = $day->named_day;
            $slovakDay = $dayTranslations[$englishDay] ?? $englishDay;
            $day->named_day = $slovakDay;

            return $day;
        });
    }
}
