<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property House $house
 * @property string $source_id
 * @property string|null $title
 * @property string $type
 * @property int|null $number
 * @property int|null $year
 * @property string|null $summary
 * @property string|null $presented_on
 * @property string|null $status
 * @property string $source_url
 */
class Proposition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['house' => House::class];
    }
}
