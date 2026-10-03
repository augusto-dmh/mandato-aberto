<?php

namespace App\Presenters;

use DateTimeInterface;

final class Dates
{
    /** A calendar date as the site writes it: `DD/MM/AAAA`. */
    public static function br(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }
}
