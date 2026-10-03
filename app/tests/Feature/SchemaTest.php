<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

// Checks C14 and C17 of .specs/features/app-skeleton/checks.md.

function insertRow(string $table, array $row): int
{
    return DB::transaction(fn () => DB::table($table)->insertGetId([...$row, 'created_at' => now(), 'updated_at' => now()]));
}

test('enforces the natural keys and text source ids', function () {
    DB::table('legislatures')->insert(['number' => 57]);
    $member = ['house' => 'camara', 'source_id' => '1', 'name' => 'A', 'party' => 'P', 'uf' => 'SP', 'source_url' => 'https://x'];
    $memberId = insertRow('members', $member);
    $proposition = ['house' => 'camara', 'source_id' => '9'];
    $propositionId = insertRow('propositions', $proposition);
    $rollCall = [
        'house' => 'camara', 'source_id' => '1-1', 'legislature_number' => 57, 'date' => '2025-01-01', 'organ' => 'PLEN',
        'description' => 'd', 'secret' => false, 'tally_yes' => 0, 'tally_no' => 0, 'tally_others' => 0, 'source_url' => 'https://x',
    ];
    $rollCallId = insertRow('roll_calls', $rollCall);
    $membership = [
        'member_id' => $memberId, 'legislature_number' => 57,
        'participation_count' => 0, 'participation_total' => 0, 'government_alignment_count' => 0, 'government_alignment_total' => 0,
        'party_alignment_count' => 0, 'party_alignment_total' => 0, 'authored_count' => 0, 'first_signer_count' => 0, 'requirements_count' => 0,
    ];
    $membershipId = insertRow('memberships', $membership);
    $vote = ['roll_call_id' => $rollCallId, 'member_id' => $memberId, 'vote' => 'Sim', 'party' => 'P'];
    insertRow('votes', $vote);
    $authorship = ['membership_id' => $membershipId, 'proposition_id' => $propositionId];
    insertRow('authorships', $authorship);

    $duplicates = [
        'members' => [...$member, 'name' => 'B'],
        'roll_calls' => [...$rollCall, 'description' => 'other'],
        'propositions' => [...$proposition, 'title' => 'other'],
        'memberships' => $membership,
        'votes' => [...$vote, 'vote' => 'Não'],
        'authorships' => $authorship,
    ];
    foreach ($duplicates as $table => $row) {
        expect(fn () => insertRow($table, $row))->toThrow(UniqueConstraintViolationException::class);
    }

    foreach (['members', 'roll_calls', 'propositions'] as $table) {
        $type = DB::selectOne('select data_type from information_schema.columns where table_name = ? and column_name = ?', [$table, 'source_id'])->data_type;
        expect($type)->toBe('text');
    }
});

test('rejects a house outside camara and senado', function (string $table, array $row) {
    DB::table('legislatures')->insert(['number' => 57]);

    expect(insertRow($table, [...$row, 'house' => 'senado']))->toBeInt();
    foreach (['presidencia', 'Camara', ''] as $house) {
        try {
            insertRow($table, [...$row, 'house' => $house, 'source_id' => "bad-{$house}"]);
            $this->fail("a {$table} row of house '{$house}' was accepted");
        } catch (QueryException $e) {
            expect($e->getCode())->toBe('23514') // check_violation
                ->and($e->getMessage())->toContain("{$table}_house_check");
        }
    }
})->with([
    'members' => ['members', ['source_id' => '1', 'name' => 'A', 'party' => 'P', 'uf' => 'SP', 'source_url' => 'https://x']],
    'propositions' => ['propositions', ['source_id' => '9']],
    'roll_calls' => ['roll_calls', [
        'source_id' => '1-1', 'legislature_number' => 57, 'date' => '2025-01-01', 'organ' => 'PLEN', 'description' => 'd',
        'secret' => false, 'tally_yes' => 0, 'tally_no' => 0, 'tally_others' => 0, 'source_url' => 'https://x',
    ]],
]);

test('runs on postgresql 18', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(intdiv((int) DB::selectOne('show server_version_num')->server_version_num, 10000))->toBe(18);
});
