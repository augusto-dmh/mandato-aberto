<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One rule of a house's published table, in the order the ETL applied it (contract-v3 door 6).
 *
 * @property int $id
 * @property House $house
 * @property string $rule_id
 * @property int $position
 * @property string $kind
 * @property string $field
 * @property string $pattern
 * @property string $description
 */
class ClassificationRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['house' => House::class];
    }
}
