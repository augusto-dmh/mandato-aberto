<?php

namespace App\Contract;

/**
 * Every legislature-bound record of one house in one legislature, keyed by the house's own ids:
 * what an import upserts and then sweeps for that (house, legislature) (plan door 1).
 *
 * @phpstan-type PeriodRecord array{starts_at: string, ends_at: string}
 * @phpstan-type MembershipRecord array{member_source_id: string, party: string, uf: string, participation_all_count: int, participation_all_total: int, participation_merit_count: int, participation_merit_total: int, government_alignment_all_count: int, government_alignment_all_total: int, government_alignment_merit_count: int, government_alignment_merit_total: int, party_alignment_all_count: int, party_alignment_all_total: int, party_alignment_merit_count: int, party_alignment_merit_total: int, symbolic_merit: int|null, authored_count: int, first_signer_count: int, requirements_count: int, periods: list<PeriodRecord>}
 * @phpstan-type RollCallRecord array{source_id: string, date: string, organ: string, description: string, proposition_source_id: string|null, approved: bool|null, ballot: string, kind: string, kind_rule: string|null, tally_yes: int|null, tally_no: int|null, tally_others: int|null, government_orientation: string|null, source_url: string, opening_description: string|null, last_presentation_description: string|null}
 * @phpstan-type VoteRecord array{roll_call_source_id: string, member_source_id: string, official: string, position: string, party: string, party_majority: string|null}
 * @phpstan-type AuthorshipRecord array{member_source_id: string, proposition_source_id: string, first_signer: bool}
 */
final readonly class Scope
{
    /**
     * @param  list<MembershipRecord>  $memberships
     * @param  list<RollCallRecord>  $rollCalls
     * @param  list<VoteRecord>  $votes
     * @param  list<AuthorshipRecord>  $authorships
     */
    public function __construct(
        public string $house,
        public int $legislature,
        public array $memberships,
        public array $rollCalls,
        public array $votes,
        public array $authorships,
    ) {}
}
