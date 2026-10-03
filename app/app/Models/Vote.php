<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $roll_call_id
 * @property int $member_id
 * @property string $official the house's value verbatim (AD-018 generalises the Senate's leave codes)
 * @property string $position `yes`, `no`, `abstention`, `obstruction`, `presiding`, `secret` or `notVoting`
 * @property string $party
 * @property string|null $party_majority
 * @property-read RollCall $rollCall
 * @property-read Member $member
 */
class Vote extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<RollCall, $this> */
    public function rollCall(): BelongsTo
    {
        return $this->belongsTo(RollCall::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
