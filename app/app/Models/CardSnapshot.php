<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * The exact values one card showed, stored the first time that card is served (share-cards door 8).
 * Append-only: nothing in the app updates or deletes a snapshot.
 *
 * @property int $id
 * @property string $code
 * @property string $digest
 * @property string $kind
 * @property string $house
 * @property string $source_id
 * @property int|null $legislature
 * @property int $template
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class CardSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
