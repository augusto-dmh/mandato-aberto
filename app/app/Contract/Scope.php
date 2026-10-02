<?php

namespace App\Contract;

/**
 * Every record of one house in one legislature, keyed by the house's own ids.
 *
 * @phpstan-type MemberRecord array{source_id: string, name: string, party: string, uf: string, source_url: string}
 * @phpstan-type MembershipRecord array{member_source_id: string, participation_count: int, participation_total: int, government_alignment_count: int, government_alignment_total: int, party_alignment_count: int, party_alignment_total: int, authored_count: int, first_signer_count: int, requirements_count: int}
 * @phpstan-type PropositionRecord array{source_id: string, title: string|null, summary: string|null}
 * @phpstan-type RollCallRecord array{source_id: string, date: string, organ: string, description: string, proposition_source_id: string|null, approved: bool|null, secret: bool, tally_yes: int, tally_no: int, tally_others: int, government_orientation: string|null, source_url: string}
 * @phpstan-type VoteRecord array{roll_call_source_id: string, member_source_id: string, vote: string, party: string, party_majority: string|null}
 * @phpstan-type AuthorshipRecord array{member_source_id: string, proposition_source_id: string}
 */
final readonly class Scope
{
    /**
     * @param  list<MemberRecord>  $members
     * @param  list<MembershipRecord>  $memberships
     * @param  list<PropositionRecord>  $propositions  roll-call propositions carry a title; authored ones only their id
     * @param  list<RollCallRecord>  $rollCalls
     * @param  list<VoteRecord>  $votes
     * @param  list<AuthorshipRecord>  $authorships
     */
    public function __construct(
        public string $house,
        public int $legislature,
        public array $members,
        public array $memberships,
        public array $propositions,
        public array $rollCalls,
        public array $votes,
        public array $authorships,
    ) {}
}
