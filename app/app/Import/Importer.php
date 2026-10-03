<?php

namespace App\Import;

use App\Contract\Scope;
use App\Contract\Snapshot;
use App\Models\ContractImport;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Loads a snapshot as the whole truth of each (house, legislature) it covers (plan door 6): one
 * transaction under a PostgreSQL advisory lock upserts every record by its natural key, then
 * deletes the memberships, roll calls, votes and authorships of that scope the snapshot no longer
 * has. Members and propositions are never swept.
 */
final class Importer
{
    /** Key of the session-level advisory lock that serialises imports. */
    public const LOCK_KEY = 57_210_057;

    private const CHUNK = 1000;

    public function import(Snapshot $snapshot, string $metaSha256): ContractImport
    {
        $db = DB::connection();
        if (! $db->selectOne('select pg_try_advisory_lock(?) as locked', [self::LOCK_KEY])->locked) {
            throw new ImportLocked('another import is running');
        }

        try {
            return $db->transaction(function () use ($db, $snapshot, $metaSha256) {
                foreach ($snapshot->scopes as $scope) {
                    $this->load($db, $scope, Carbon::now());
                }
                $counts = $snapshot->counts();

                return ContractImport::query()->create([
                    'schema_version' => $snapshot->schemaVersion,
                    'generated_at' => $snapshot->generatedAt,
                    'meta_sha256' => $metaSha256,
                    'members_count' => $counts['members'],
                    'roll_calls_count' => $counts['roll_calls'],
                    'votes_count' => $counts['votes'],
                    'propositions_count' => $counts['propositions'],
                ]);
            });
        } finally {
            $db->select('select pg_advisory_unlock(?)', [self::LOCK_KEY]);
        }
    }

    private function load(ConnectionInterface $db, Scope $scope, Carbon $now): void
    {
        $house = $scope->house;
        $stamp = ['created_at' => $now, 'updated_at' => $now];

        $db->table('legislatures')->insertOrIgnore(['number' => $scope->legislature, ...$stamp]);

        $this->upsert($db, 'members', array_map(fn (array $m) => ['house' => $house, ...$m, ...$stamp], $scope->members),
            ['house', 'source_id'], ['name', 'party', 'uf', 'source_url', 'updated_at']);
        $memberIds = $this->ids($db, 'members', $house);

        $this->upsert($db, 'memberships', array_map(function (array $m) use ($memberIds, $scope, $stamp) {
            $member = $m['member_source_id'];
            unset($m['member_source_id']);

            return ['member_id' => $memberIds[$member], 'legislature_number' => $scope->legislature, ...$m, ...$stamp];
        }, $scope->memberships), ['member_id', 'legislature_number'], [
            'participation_count', 'participation_total', 'government_alignment_count', 'government_alignment_total',
            'party_alignment_count', 'party_alignment_total', 'authored_count', 'first_signer_count', 'requirements_count', 'updated_at',
        ]);
        $membershipIds = $db->table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.house', $house)->where('memberships.legislature_number', $scope->legislature)
            ->pluck('memberships.id', 'members.source_id')->all();

        // A roll call supplies a proposition's title; authorship alone only makes sure the row exists.
        $titled = array_values(array_filter($scope->propositions, fn (array $p) => $p['title'] !== null));
        $bare = array_values(array_filter($scope->propositions, fn (array $p) => $p['title'] === null));
        $this->upsert($db, 'propositions', array_map(fn (array $p) => ['house' => $house, ...$p, ...$stamp], $titled),
            ['house', 'source_id'], ['title', 'summary', 'updated_at']);
        $this->upsert($db, 'propositions', array_map(fn (array $p) => ['house' => $house, 'source_id' => $p['source_id'], ...$stamp], $bare),
            ['house', 'source_id'], []);
        $propositionIds = $this->ids($db, 'propositions', $house);

        $this->upsert($db, 'roll_calls', array_map(function (array $r) use ($house, $scope, $propositionIds, $stamp) {
            $proposition = $r['proposition_source_id'];
            unset($r['proposition_source_id']);

            return [
                'house' => $house, 'legislature_number' => $scope->legislature,
                'proposition_id' => $proposition === null ? null : $propositionIds[$proposition], ...$r, ...$stamp,
            ];
        }, $scope->rollCalls), ['house', 'source_id'], [
            'legislature_number', 'date', 'organ', 'description', 'proposition_id', 'approved', 'secret',
            'tally_yes', 'tally_no', 'tally_others', 'government_orientation', 'source_url', 'updated_at',
        ]);
        $rollCallIds = $this->ids($db, 'roll_calls', $house);

        $votes = array_map(fn (array $v) => [
            'roll_call_id' => $rollCallIds[$v['roll_call_source_id']],
            'member_id' => $memberIds[$v['member_source_id']],
            'vote' => $v['vote'],
            'party' => $v['party'],
            'party_majority' => $v['party_majority'],
            ...$stamp,
        ], $scope->votes);
        $this->upsert($db, 'votes', $votes, ['roll_call_id', 'member_id'], ['vote', 'party', 'party_majority', 'updated_at']);

        $authorships = array_map(fn (array $a) => [
            'membership_id' => $membershipIds[$a['member_source_id']],
            'proposition_id' => $propositionIds[$a['proposition_source_id']],
            ...$stamp,
        ], $scope->authorships);
        $this->upsert($db, 'authorships', $authorships, ['membership_id', 'proposition_id'], []);

        $this->sweep($db, $scope, $votes, $authorships);
    }

    /**
     * @param  list<array{roll_call_id: int, member_id: int}>  $votes
     * @param  list<array{membership_id: int, proposition_id: int}>  $authorships
     */
    private function sweep(ConnectionInterface $db, Scope $scope, array $votes, array $authorships): void
    {
        $inScope = fn (Builder $q) => $q->select('id')->from('roll_calls')
            ->where('house', $scope->house)->where('legislature_number', $scope->legislature);
        $membershipsInScope = fn (Builder $q) => $q->select('memberships.id')->from('memberships')
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.house', $scope->house)->where('memberships.legislature_number', $scope->legislature);

        $db->table('votes')->whereIn('roll_call_id', $inScope)
            ->whereRaw('(roll_call_id, member_id) not in (select * from unnest(?::bigint[], ?::bigint[]))', [
                $this->array(array_column($votes, 'roll_call_id')), $this->array(array_column($votes, 'member_id')),
            ])->delete();
        $db->table('authorships')->whereIn('membership_id', $membershipsInScope)
            ->whereRaw('(membership_id, proposition_id) not in (select * from unnest(?::bigint[], ?::bigint[]))', [
                $this->array(array_column($authorships, 'membership_id')), $this->array(array_column($authorships, 'proposition_id')),
            ])->delete();
        $db->table('roll_calls')->where('house', $scope->house)->where('legislature_number', $scope->legislature)
            ->whereRaw('not (source_id = any(?::text[]))', [$this->array(array_column($scope->rollCalls, 'source_id'))])
            ->delete();
        $db->table('memberships')->whereIn('id', $membershipsInScope)
            ->whereNotIn('member_id', fn (Builder $q) => $q->select('id')->from('members')->where('house', $scope->house)
                ->whereRaw('source_id = any(?::text[])', [$this->array(array_column($scope->members, 'source_id'))]))
            ->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $keys
     * @param  list<string>  $update  empty: insert what is missing, change nothing
     */
    private function upsert(ConnectionInterface $db, string $table, array $rows, array $keys, array $update): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $update === []
                ? $db->table($table)->insertOrIgnore($chunk)
                : $db->table($table)->upsert($chunk, $keys, $update);
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
