<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;

/**
 * One successful run of `mandato:import` (AD-005 provenance).
 *
 * @property int $id
 * @property int $schema_version
 * @property CarbonImmutable $generated_at
 * @property string $meta_sha256
 * @property int $members_count
 * @property int $roll_calls_count
 * @property int $votes_count
 * @property int $propositions_count
 */
class ContractImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['generated_at' => 'immutable_datetime'];
    }

    public static function latestRun(): ?self
    {
        return self::query()->latest('id')->first();
    }
}
