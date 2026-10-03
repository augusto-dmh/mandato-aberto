<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A parliamentarian in one house, identified by the house's own id.
 *
 * @property int $id
 * @property House $house
 * @property string $source_id
 * @property string $name
 * @property string $party
 * @property string $uf
 * @property string $photo_url fetched by `mandato:photos`; pages show our cached copy (share-cards)
 * @property string $source_url
 */
class Member extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['house' => House::class];
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return HasMany<Vote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
