<?php

namespace App\Import;

use App\Contract\ContractException;
use App\Contract\Scope;
use App\Contract\Snapshot;
use App\Models\ContractImport;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Loads one house directory as the whole truth of that house (plan doors 2 and 3, skeleton door 6):
 * one transaction upserts every record by its natural key, deletes the memberships, exercise
 * periods, roll calls, votes and authorships of each listed (house, legislature) the snapshot no
 * longer has, and replaces the house's classification rules and full texts as sets. Members and
 * propositions are never swept. The caller holds the advisory lock (`lock()`), once per run.
 */
final class Importer
{
    /** Key of the session-level advisory lock that serialises imports. */
    public const LOCK_KEY = 57_210_057;

    private const CHUNK = 1000;

    /**
     * Runs `$work` while holding the import lock, or refuses when another import holds it.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public function locked(callable $work): mixed
    {
        $db = DB::connection();
        if (! $db->selectOne('select pg_try_advisory_lock(?) as locked', [self::LOCK_KEY])->locked) {
            throw new ImportLocked('another import is running');
        }
        try {
            return $work();
        } finally {
            $db->select('select pg_advisory_unlock(?)', [self::LOCK_KEY]);
        }
    }

    /** Refuses a legislature whose dates differ from the stored ones (AC 11); reads, never writes. */
    public function checkLegislatures(Snapshot $snapshot): void
    {
        $stored = DB::table('legislatures')->whereIn('number', array_column($snapshot->legislatures, 'number'))
            ->get(['number', 'starts_on', 'ends_on'])->keyBy('number');
        foreach ($snapshot->legislatures as $l) {
            $s = $stored[$l['number']] ?? null;
            if ($s !== null && ($s->starts_on !== $l['starts_on'] || $s->ends_on !== $l['ends_on'])) {
                throw new ContractException("legislature {$l['number']} dates differ: stored {$s->starts_on}..{$s->ends_on}, contract {$l['starts_on']}..{$l['ends_on']}");
            }
        }
    }

    public function import(Snapshot $snapshot, string $metaSha256): ContractImport
    {
        $db = DB::connection();

        return $db->transaction(function () use ($db, $snapshot, $metaSha256) {
            $this->checkLegislatures($snapshot);
            $now = Carbon::now();
            $stamp = ['created_at' => $now, 'updated_at' => $now];
            $house = $snapshot->house;

            $db->table('legislatures')->insertOrIgnore(array_map(fn (array $l) => [...$l, ...$stamp], $snapshot->legislatures));

            $this->upsert($db, 'members', array_map(fn (array $m) => ['house' => $house, ...$m, ...$stamp], $snapshot->members),
                ['house', 'source_id'], ['name', 'party', 'uf', 'photo_url', 'source_url', 'updated_at']);
            $this->upsert($db, 'propositions', array_map(fn (array $p) => ['house' => $house, ...$p, ...$stamp], $snapshot->propositions),
                ['house', 'source_id'], ['type', 'number', 'year', 'summary', 'presented_on', 'status', 'source_url', 'updated_at']);
            $memberIds = $this->ids($db, 'members', $house);
            $propositionIds = $this->ids($db, 'propositions', $house);

            foreach ($snapshot->scopes as $scope) {
                $this->load($db, $scope, $memberIds, $propositionIds, $stamp);
            }
            $this->replaceRules($db, $snapshot, $stamp);
            $this->replaceFullTexts($db, $snapshot, $propositionIds, $stamp);

            $counts = $snapshot->counts();

            return ContractImport::query()->create([
                'house' => $house,
                'schema_version' => $snapshot->schemaVersion,
                'generated_at' => $snapshot->generatedAt,
                'meta_sha256' => $metaSha256,
                'classification_version' => $snapshot->classificationVersion,
                'coverage' => $snapshot->coverage,
                'members_count' => $counts['members'],
                'mandates_count' => $counts['mandates'],
                'roll_calls_count' => $counts['roll_calls'],
                'votes_count' => $counts['votes'],
                'propositions_count' => $counts['propositions'],
                'full_texts_count' => $counts['full_texts'],
            ]);
        });
    }

    /**
     * @param  array<string, int>  $memberIds
     * @param  array<string, int>  $propositionIds
     * @param  array<string, Carbon>  $stamp
     */
    private function load(ConnectionInterface $db, Scope $scope, array $memberIds, array $propositionIds, array $stamp): void
    {
        $house = $scope->house;
        $legislature = $scope->legislature;

        $this->upsert($db, 'memberships', array_map(function (array $m) use ($memberIds, $legislature, $stamp) {
            $member = $m['member_source_id'];
            unset($m['member_source_id'], $m['periods']);

            return ['member_id' => $memberIds[$member], 'legislature_number' => $legislature, ...$m, ...$stamp];
        }, $scope->memberships), ['member_id', 'legislature_number'], [
            'party', 'uf',
            ...array_merge(...array_map(fn (string $i) => ["{$i}_all_count", "{$i}_all_total", "{$i}_merit_count", "{$i}_merit_total"],
                ['participation', 'government_alignment', 'party_alignment'])),
            'symbolic_merit', 'authored_count', 'first_signer_count', 'requirements_count', 'updated_at',
        ]);
        $membershipIds = $db->table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.house', $house)->where('memberships.legislature_number', $legislature)
            ->pluck('memberships.id', 'members.source_id')->all();

        $periods = [];
        foreach ($scope->memberships as $m) {
            foreach ($m['periods'] as $p) {
                $periods[] = ['membership_id' => $membershipIds[$m['member_source_id']], ...$p, ...$stamp];
            }
        }
        $this->upsert($db, 'exercise_periods', $periods, ['membership_id', 'starts_at'], ['ends_at', 'updated_at']);

        $this->upsert($db, 'roll_calls', array_map(function (array $r) use ($house, $legislature, $propositionIds, $stamp) {
            $proposition = $r['proposition_source_id'];
            unset($r['proposition_source_id']);

            return [
                'house' => $house, 'legislature_number' => $legislature,
                'proposition_id' => $proposition === null ? null : $propositionIds[$proposition], ...$r, ...$stamp,
            ];
        }, $scope->rollCalls), ['house', 'source_id'], [
            'legislature_number', 'date', 'organ', 'description', 'proposition_id', 'approved', 'ballot', 'kind', 'kind_rule',
            'tally_yes', 'tally_no', 'tally_others', 'government_orientation', 'source_url',
            'opening_description', 'last_presentation_description', 'updated_at',
        ]);
        $rollCallIds = $this->ids($db, 'roll_calls', $house);

        $votes = array_map(fn (array $v) => [
            'roll_call_id' => $rollCallIds[$v['roll_call_source_id']],
            'member_id' => $memberIds[$v['member_source_id']],
            'official' => $v['official'],
            'position' => $v['position'],
            'party' => $v['party'],
            'party_majority' => $v['party_majority'],
            ...$stamp,
        ], $scope->votes);
        $this->upsert($db, 'votes', $votes, ['roll_call_id', 'member_id'], ['official', 'position', 'party', 'party_majority', 'updated_at']);

        $authorships = array_map(fn (array $a) => [
            'membership_id' => $membershipIds[$a['member_source_id']],
            'proposition_id' => $propositionIds[$a['proposition_source_id']],
            'first_signer' => $a['first_signer'],
            ...$stamp,
        ], $scope->authorships);
        $this->upsert($db, 'authorships', $authorships, ['membership_id', 'proposition_id'], ['first_signer', 'updated_at']);

        $this->sweep($db, $scope, $memberIds, $periods, $votes, $authorships);
    }

    /**
     * @param  array<string, int>  $memberIds
     * @param  list<array{membership_id: int, starts_at: string}>  $periods
     * @param  list<array{roll_call_id: int, member_id: int}>  $votes
     * @param  list<array{membership_id: int, proposition_id: int}>  $authorships
     */
    private function sweep(ConnectionInterface $db, Scope $scope, array $memberIds, array $periods, array $votes, array $authorships): void
    {
        $inScope = fn (Builder $q) => $q->select('id')->from('roll_calls')
            ->where('house', $scope->house)->where('legislature_number', $scope->legislature);
        $membershipsInScope = fn (Builder $q) => $q->select('memberships.id')->from('memberships')
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.house', $scope->house)->where('memberships.legislature_number', $scope->legislature);
        $kept = array_map(fn (array $m) => $memberIds[$m['member_source_id']], $scope->memberships);

        $db->table('votes')->whereIn('roll_call_id', $inScope)
            ->whereRaw('(roll_call_id, member_id) not in (select * from unnest(?::bigint[], ?::bigint[]))', [
                $this->array(array_column($votes, 'roll_call_id')), $this->array(array_column($votes, 'member_id')),
            ])->delete();
        $db->table('authorships')->whereIn('membership_id', $membershipsInScope)
            ->whereRaw('(membership_id, proposition_id) not in (select * from unnest(?::bigint[], ?::bigint[]))', [
                $this->array(array_column($authorships, 'membership_id')), $this->array(array_column($authorships, 'proposition_id')),
            ])->delete();
        $db->table('exercise_periods')->whereIn('membership_id', $membershipsInScope)
            ->whereRaw('(membership_id, starts_at) not in (select * from unnest(?::bigint[], ?::timestamp[]))', [
                $this->array(array_column($periods, 'membership_id')), $this->array(array_column($periods, 'starts_at')),
            ])->delete();
        $db->table('roll_calls')->where('house', $scope->house)->where('legislature_number', $scope->legislature)
            ->whereRaw('not (source_id = any(?::text[]))', [$this->array(array_column($scope->rollCalls, 'source_id'))])
            ->delete();
        $db->table('memberships')->whereIn('id', $membershipsInScope)
            ->whereRaw('not (member_id = any(?::bigint[]))', [$this->array($kept)])
            ->delete();
    }

    /** @param  array<string, Carbon>  $stamp */
    private function replaceRules(ConnectionInterface $db, Snapshot $snapshot, array $stamp): void
    {
        $rows = [];
        foreach ($snapshot->rules as $position => $rule) {
            $rows[] = ['house' => $snapshot->house, ...$rule, 'position' => $position + 1, ...$stamp];
        }
        $this->upsert($db, 'classification_rules', $rows, ['house', 'rule_id'], ['position', 'kind', 'field', 'pattern', 'description', 'updated_at']);
        $db->table('classification_rules')->where('house', $snapshot->house)
            ->whereRaw('not (rule_id = any(?::text[]))', [$this->array(array_column($snapshot->rules, 'rule_id'))])
            ->delete();
    }

    /**
     * @param  array<string, int>  $propositionIds
     * @param  array<string, Carbon>  $stamp
     */
    private function replaceFullTexts(ConnectionInterface $db, Snapshot $snapshot, array $propositionIds, array $stamp): void
    {
        $rows = array_map(function (array $t) use ($propositionIds, $stamp) {
            $proposition = $t['proposition_source_id'];
            unset($t['proposition_source_id']);

            return ['proposition_id' => $propositionIds[$proposition], ...$t, ...$stamp];
        }, $snapshot->fullTexts);
        $this->upsert($db, 'full_texts', $rows, ['proposition_id'], ['source_url', 'document_sha256', 'extractor', 'extracted_at', 'text', 'updated_at']);
        $db->table('full_texts')
            ->whereIn('proposition_id', fn (Builder $q) => $q->select('id')->from('propositions')->where('house', $snapshot->house))
            ->whereRaw('not (proposition_id = any(?::bigint[]))', [$this->array(array_column($rows, 'proposition_id'))])
            ->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $keys
     * @param  list<string>  $update
     */
    private function upsert(ConnectionInterface $db, string $table, array $rows, array $keys, array $update): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $db->table($table)->upsert($chunk, $keys, $update);
        }
    }

    /** @return array<string, int> source id => row id */
    private function ids(ConnectionInterface $db, string $table, string $house): array
    {
        return $db->table($table)->where('house', $house)->pluck('id', 'source_id')->all();
    }

    /** @param  array<int|string>  $values  a PostgreSQL array literal, bound as one parameter */
    private function array(array $values): string
    {
        return '{'.implode(',', array_map(fn ($v) => '"'.addcslashes((string) $v, '"\\').'"', $values)).'}';
    }
}
