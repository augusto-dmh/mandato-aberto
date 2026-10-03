<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member in one legislature (the contract's mandate), with its own party and UF and the
 * indicators the contract computes over it on two bases, `all` and `merit` (AD-004, plan door 3).
 *
 * @property int $id
 * @property int $member_id
 * @property int $legislature_number
 * @property string $party
 * @property string $uf
 * @property int $participation_all_count
 * @property int $participation_all_total
 * @property int $participation_merit_count
 * @property int $participation_merit_total
 * @property int $government_alignment_all_count
 * @property int $government_alignment_all_total
 * @property int $government_alignment_merit_count
 * @property int $government_alignment_merit_total
 * @property int $party_alignment_all_count
 * @property int $party_alignment_all_total
 * @property int $party_alignment_merit_count
 * @property int $party_alignment_merit_total
 * @property int|null $symbolic_merit null when the house publishes no symbolic roll calls, never 0
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

    /** @return BelongsTo<Legislature, $this> */
    public function legislature(): BelongsTo
    {
        return $this->belongsTo(Legislature::class, 'legislature_number');
    }
}
