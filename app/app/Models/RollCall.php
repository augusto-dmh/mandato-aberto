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
 * @property string $ballot `nominal`, `secret` or `symbolic`
 * @property string $kind `final`, `amendment`, `procedural` or `unclassified`
 * @property string|null $kind_rule
 * @property int|null $tally_yes all three null, or none (plan door 3)
 * @property int|null $tally_no
 * @property int|null $tally_others
 * @property string|null $government_orientation
 * @property string|null $opening_description
 * @property string|null $last_presentation_description
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
