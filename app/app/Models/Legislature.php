<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $number
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 */
class Legislature extends Model
{
    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }
}
