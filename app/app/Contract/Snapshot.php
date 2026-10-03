<?php

namespace App\Contract;

/**
 * One house directory normalised to the stored shape (plan door 1): the house-level records
 * (members, propositions, rules, full texts, legislature dates, coverage) and one scope per
 * legislature the directory lists, each upserted and swept on its own.
 *
 * @phpstan-type LegislatureRecord array{number: int, starts_on: string, ends_on: string}
 * @phpstan-type MemberRecord array{source_id: string, name: string, party: string, uf: string, photo_url: string, source_url: string}
 * @phpstan-type PropositionRecord array{source_id: string, type: string, number: int|null, year: int|null, summary: string|null, presented_on: string|null, status: string|null, source_url: string}
 * @phpstan-type RuleRecord array{rule_id: string, kind: string, field: string, pattern: string, description: string}
 * @phpstan-type FullTextRecord array{proposition_source_id: string, source_url: string, document_sha256: string, extractor: string, extracted_at: string, text: string}
 */
final readonly class Snapshot
{
    /**
     * @param  list<LegislatureRecord>  $legislatures
     * @param  list<array<string, mixed>>  $coverage  `meta.coverage` as published
     * @param  list<MemberRecord>  $members
     * @param  list<PropositionRecord>  $propositions
     * @param  list<RuleRecord>  $rules  in file order
     * @param  list<FullTextRecord>  $fullTexts
     * @param  list<Scope>  $scopes
     */
    public function __construct(
        public int $schemaVersion,
        public string $house,
        public string $generatedAt,
        public int $classificationVersion,
        public array $legislatures,
        public array $coverage,
        public array $members,
        public array $propositions,
        public array $rules,
        public array $fullTexts,
        public array $scopes,
    ) {}

    /** @return array{members: int, mandates: int, roll_calls: int, votes: int, propositions: int, full_texts: int} */
    public function counts(): array
    {
        $sum = fn (string $records) => array_sum(array_map(fn (Scope $s) => count($s->{$records}), $this->scopes));

        return [
            'members' => count($this->members),
            'mandates' => $sum('memberships'),
            'roll_calls' => $sum('rollCalls'),
            'votes' => $sum('votes'),
            'propositions' => count($this->propositions),
            'full_texts' => count($this->fullTexts),
        ];
    }

    public function summary(): string
    {
        $c = $this->counts();

        return "schema_version {$this->schemaVersion} {$this->house} generated {$this->generatedAt}: "
            ."{$c['members']} members, {$c['mandates']} mandates, {$c['roll_calls']} roll calls, {$c['votes']} votes, "
            ."{$c['propositions']} propositions, {$c['full_texts']} full texts";
    }
}
