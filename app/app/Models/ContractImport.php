<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One house imported by one run of `mandato:import` (AD-005 provenance, plan door 3).
 *
 * @property int $id
 * @property House $house
 * @property int $schema_version
 * @property CarbonImmutable $generated_at
 * @property string $meta_sha256
 * @property int $classification_version
 * @property list<array{legislature: int, through: string|null, rollCalls: array{nominal: int, secret: int, symbolic: int|null}, unclassified: int, members: int}> $coverage
 * @property int $members_count
 * @property int $mandates_count
 * @property int $roll_calls_count
 * @property int $votes_count
 * @property int $propositions_count
 * @property int $full_texts_count
 */
class ContractImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['house' => House::class, 'generated_at' => 'immutable_datetime', 'coverage' => 'array'];
    }

    /** The latest import of `$house`: what that house's pages say was collected, and when. */
    public static function latestOf(House $house): ?self
    {
        return self::query()->where('house', $house)->latest('id')->first();
    }
}
