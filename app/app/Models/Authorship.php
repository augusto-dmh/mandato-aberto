<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $membership_id
 * @property int $proposition_id
 */
class Authorship extends Model
{
    protected $guarded = [];
}
