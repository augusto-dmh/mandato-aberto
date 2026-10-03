<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property House $house
 * @property string $source_id
 * @property string|null $title
 * @property string|null $summary
 */
class Proposition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['house' => House::class];
    }
}
