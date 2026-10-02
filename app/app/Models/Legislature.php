<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $number
 */
class Legislature extends Model
{
    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $guarded = [];
}
