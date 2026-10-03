<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One set of official photo bytes fetched for one member, named by its SHA-256 (share-cards door 2).
 * Rows are never deleted; only `checked_at` moves, each time the house answers the same bytes again.
 *
 * @property int $id
 * @property string $house
 * @property string $member_source_id
 * @property string $sha256
 * @property string $source_url
 * @property string $final_url
 * @property int $bytes
 * @property int $width
 * @property int $height
 * @property CarbonImmutable $fetched_at
 * @property CarbonImmutable $checked_at
 */
class PhotoVersion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fetched_at' => 'immutable_datetime', 'checked_at' => 'immutable_datetime'];
    }
}
