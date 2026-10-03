<?php

namespace App\Contract;

/**
 * A contract normalised to the stored shape: one or more scopes, each a (house, legislature)
 * snapshot the importer upserts and sweeps on its own (plan door 6).
 */
final readonly class Snapshot
{
    /** @param list<Scope> $scopes */
    public function __construct(
        public int $schemaVersion,
        public string $generatedAt,
        public array $scopes,
    ) {}

    /** @return array{members: int, roll_calls: int, votes: int, propositions: int} */
    public function counts(): array
    {
        $propositions = [];
        $counts = ['members' => 0, 'roll_calls' => 0, 'votes' => 0];
        foreach ($this->scopes as $scope) {
            $counts['members'] += count($scope->members);
            $counts['roll_calls'] += count($scope->rollCalls);
            $counts['votes'] += count($scope->votes);
            foreach ($scope->propositions as $proposition) {
                $propositions["{$scope->house}:{$proposition['source_id']}"] = true;
            }
        }

        return [...$counts, 'propositions' => count($propositions)];
    }

    public function summary(): string
    {
        $c = $this->counts();

        return "schema_version {$this->schemaVersion} generated {$this->generatedAt}: "
            ."{$c['members']} members, {$c['roll_calls']} roll calls, {$c['votes']} votes, {$c['propositions']} propositions";
    }
}
