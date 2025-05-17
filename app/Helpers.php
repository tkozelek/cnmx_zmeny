<?php

namespace App;

class Helpers
{
    public static function findIdInArray($id, $array): bool
    {
        foreach ($array as $key => $val) {
            if ($val['id'] == $id) {
                return true;
            }
        }

        return false;
    }

    public static function getDateFromAttribute($value, $format): string
    {
        return \Carbon\Carbon::parse($value)->format($format);
    }
}
