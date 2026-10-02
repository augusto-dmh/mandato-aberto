<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property House $house
 * @property string $source_id
 * @property int $legislature_number
 * @property Carbon $date
 * @property string $organ
 * @property string $description
 * @property int|null $proposition_id
 * @property bool|null $approved
 * @property bool $secret
 * @property int $tally_yes
 * @property int $tally_no
 * @property int $tally_others
 * @property string|null $government_orientation
 * @property string $source_url
 * @property-read Proposition|null $proposition
 */
class RollCall extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'house' => House::class,
            'date' => 'date',
            'approved' => 'boolean',
            'secret' => 'boolean',
        ];
    }

    /** @return BelongsTo<Proposition, $this> */
    public function proposition(): BelongsTo
    {
        return $this->belongsTo(Proposition::class);
    }

    /** @return HasMany<Vote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
