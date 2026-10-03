<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member in one legislature, with the indicators the contract computes over it (AD-004).
 *
 * @property int $id
 * @property int $member_id
 * @property int $legislature_number
 * @property int $participation_count
 * @property int $participation_total
 * @property int $government_alignment_count
 * @property int $government_alignment_total
 * @property int $party_alignment_count
 * @property int $party_alignment_total
 * @property int $authored_count
 * @property int $first_signer_count
 * @property int $requirements_count
 */
class Membership extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
