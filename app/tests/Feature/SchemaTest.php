<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Checks C29-C33 of .specs/features/app-contract-v3/checks.md (C29, C30 carry skeleton C14, C17).

function insertRow(string $table, array $row): int
{
    return DB::transaction(fn () => DB::table($table)->insertGetId([...$row, 'created_at' => now(), 'updated_at' => now()]));
}

/** The SQLSTATE an insert fails with, or null when it is accepted. */
function sqlState(Closure $write): ?string
{
    try {
        DB::transaction($write);
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

/** @return array<string, mixed> one valid row of each v3 table, keyed by table */
function v3Rows(): array
{
    DB::table('legislatures')->insert(['number' => 57, 'starts_on' => '2023-02-01', 'ends_on' => '2027-01-31']);
    $member = ['house' => 'camara', 'source_id' => '1', 'name' => 'A', 'party' => 'P', 'uf' => 'SP', 'photo_url' => 'https://x/1.jpg', 'source_url' => 'https://x'];
    $memberId = insertRow('members', $member);
    $proposition = ['house' => 'camara', 'source_id' => '9', 'type' => 'PL', 'source_url' => 'https://x'];
    $propositionId = insertRow('propositions', $proposition);
    $rollCall = [
        'house' => 'camara', 'source_id' => '1-1', 'legislature_number' => 57, 'date' => '2025-01-01', 'organ' => 'PLEN',
        'description' => 'd', 'ballot' => 'nominal', 'kind' => 'final', 'kind_rule' => 'camara.09',
        'tally_yes' => 0, 'tally_no' => 0, 'tally_others' => 0, 'source_url' => 'https://x',
    ];
    $rollCallId = insertRow('roll_calls', $rollCall);
    $membership = ['member_id' => $memberId, 'legislature_number' => 57, 'party' => 'P', 'uf' => 'SP', 'symbolic_merit' => null,
        'authored_count' => 0, 'first_signer_count' => 0, 'requirements_count' => 0];
    foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
        foreach (['all', 'merit'] as $basis) {
            $membership["{$indicator}_{$basis}_count"] = 0;
            $membership["{$indicator}_{$basis}_total"] = 0;
        }
    }
    $membershipId = insertRow('memberships', $membership);
    $vote = ['roll_call_id' => $rollCallId, 'member_id' => $memberId, 'official' => 'Sim', 'position' => 'yes', 'party' => 'P'];
    insertRow('votes', $vote);
    $authorship = ['membership_id' => $membershipId, 'proposition_id' => $propositionId, 'first_signer' => true];
    insertRow('authorships', $authorship);
    $rule = ['house' => 'camara', 'rule_id' => 'camara.01', 'position' => 1, 'kind' => 'procedural', 'field' => 'descricao', 'pattern' => 'x', 'description' => 'd'];
    insertRow('classification_rules', $rule);
    $fullText = ['proposition_id' => $propositionId, 'source_url' => 'https://x', 'document_sha256' => str_repeat('a', 64), 'extractor' => 'pypdf 6.1.0',
        'extracted_at' => '2027-01-01T00:00:00Z', 'text' => 't'];
    insertRow('full_texts', $fullText);
    $period = ['membership_id' => $membershipId, 'starts_at' => '2023-02-01 00:00:00', 'ends_at' => '2027-02-01 00:00:00'];
    insertRow('exercise_periods', $period);
    $import = ['house' => 'camara', 'schema_version' => 3, 'generated_at' => '2027-01-01T00:00:00Z', 'meta_sha256' => str_repeat('0', 64),
        'classification_version' => 1, 'coverage' => '[]', 'members_count' => 0, 'mandates_count' => 0, 'roll_calls_count' => 0,
        'votes_count' => 0, 'propositions_count' => 0, 'full_texts_count' => 0];

    return compact('member', 'memberId', 'proposition', 'propositionId', 'rollCall', 'rollCallId', 'membership', 'membershipId',
        'vote', 'authorship', 'rule', 'fullText', 'period', 'import');
}

test('enforces the natural keys and the house check', function () {
    $rows = v3Rows();

    $duplicates = [
        'members' => [...$rows['member'], 'name' => 'B'],
        'roll_calls' => [...$rows['rollCall'], 'description' => 'other'],
        'propositions' => [...$rows['proposition'], 'title' => 'other'],
        'memberships' => $rows['membership'],
        'votes' => [...$rows['vote'], 'official' => 'Não', 'position' => 'no'],
        'authorships' => $rows['authorship'],
    ];
    foreach ($duplicates as $table => $row) {
        expect(fn () => insertRow($table, $row))->toThrow(UniqueConstraintViolationException::class);
    }

    expect(insertRow('members', [...$rows['member'], 'house' => 'senado']))->toBeInt();
    try {
        insertRow('members', [...$rows['member'], 'house' => 'presidencia', 'source_id' => '2']);
        $this->fail('a member of house presidencia was accepted');
    } catch (QueryException $e) {
        expect($e->getCode())->toBe('23514'); // check_violation
    }

    foreach (['members', 'roll_calls', 'propositions'] as $table) {
        $type = DB::selectOne('select data_type from information_schema.columns where table_name = ? and column_name = ?', [$table, 'source_id'])->data_type;
        expect($type)->toBe('text');
    }
});

test('rejects a house outside camara and senado', function (string $table, string $key) {
    $row = v3Rows()[$key];

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
    'members' => ['members', 'member'],
    'propositions' => ['propositions', 'proposition'],
    'roll_calls' => ['roll_calls', 'rollCall'],
]);

test('runs on postgresql 18', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(intdiv((int) DB::selectOne('show server_version_num')->server_version_num, 10000))->toBe(18);
});

test('enforces the v3 enum and tally checks', function () {
    $rows = v3Rows();
    $rollCall = fn (array $change) => fn () => DB::table('roll_calls')->insert([...$rows['rollCall'], 'source_id' => 'x-'.bin2hex(random_bytes(3)), ...$change]);
    $vote = function (array $change) use ($rows) {
        return function () use ($rows, $change) {
            $member = DB::table('members')->insertGetId([...$rows['member'], 'source_id' => bin2hex(random_bytes(3))]);
            DB::table('votes')->insert([...$rows['vote'], 'member_id' => $member, ...$change]);
        };
    };

    $writes = [
        'ballot' => $rollCall(['ballot' => 'open']),
        'kind' => $rollCall(['kind' => 'important']),
        'tallies' => $rollCall(['tally_yes' => 1, 'tally_no' => null, 'tally_others' => null]),
        'government orientation' => $rollCall(['government_orientation' => 'liberado']),
        'position' => $vote(['position' => 'other']),
        'party majority' => $vote(['party_majority' => 'Sim']),
        'rule house' => fn () => DB::table('classification_rules')->insert([...$rows['rule'], 'rule_id' => 'x.01', 'house' => 'presidencia']),
        'rule kind' => fn () => DB::table('classification_rules')->insert([...$rows['rule'], 'rule_id' => 'camara.02', 'kind' => 'unclassified']),
        'import house' => fn () => DB::table('contract_imports')->insert([...$rows['import'], 'house' => 'presidencia']),
    ];
    foreach ($writes as $name => $write) {
        expect(sqlState($write))->toBe('23514', $name);
    }

    // The valid counterparts are accepted, so each refusal above is the check and not the row.
    expect(sqlState($rollCall(['tally_yes' => null, 'tally_no' => null, 'tally_others' => null, 'government_orientation' => 'free'])))->toBeNull()
        ->and(sqlState($vote(['position' => 'notVoting', 'party_majority' => 'yes'])))->toBeNull()
        ->and(sqlState(fn () => DB::table('contract_imports')->insert([...$rows['import'], 'house' => 'senado'])))->toBeNull();
});

test('enforces the v3 keys cascades and nullability', function () {
    $rows = v3Rows();

    expect(sqlState(fn () => DB::table('classification_rules')->insert([...$rows['rule'], 'position' => 2])))->toBe('23505')
        ->and(sqlState(fn () => DB::table('full_texts')->insert([...$rows['fullText'], 'text' => 'other'])))->toBe('23505')
        ->and(sqlState(fn () => DB::table('exercise_periods')->insert([...$rows['period'], 'ends_at' => '2024-01-01 00:00:00'])))->toBe('23505')
        ->and(sqlState(fn () => DB::table('contract_imports')->insert([...$rows['import'], 'house' => null])))->toBe('23502')
        ->and(sqlState(fn () => DB::table('memberships')->insert([...$rows['membership'], 'legislature_number' => 57, 'member_id' => DB::table('members')->insertGetId([...$rows['member'], 'source_id' => '77']), 'party' => null])))->toBe('23502');

    DB::table('authorships')->where('membership_id', $rows['membershipId'])->delete();
    DB::table('memberships')->where('id', $rows['membershipId'])->delete();
    expect(DB::table('exercise_periods')->count())->toBe(0);

    DB::table('authorships')->where('proposition_id', $rows['propositionId'])->delete();
    DB::table('propositions')->where('id', $rows['propositionId'])->delete();
    expect(DB::table('full_texts')->count())->toBe(0);

    expect(sqlState(fn () => DB::table('roll_calls')->insert([...$rows['rollCall'], 'source_id' => '2-2', 'kind_rule' => 'camara.99'])))->toBeNull();
});

test('the v3 migration empties the v2 rows', function () {
    // Rolls back the v3 migration by its path: later features add migrations after it (share-cards door 2).
    Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_10_03_000000_reshape_for_contract_v3.php', '--force' => true]);
    expect(DB::getSchemaBuilder()->hasColumn('roll_calls', 'secret'))->toBeTrue();

    DB::table('legislatures')->insert(['number' => 57]);
    $member = insertRow('members', ['house' => 'camara', 'source_id' => '101', 'name' => 'A', 'party' => 'P', 'uf' => 'SP', 'source_url' => 'https://x']);
    $proposition = insertRow('propositions', ['house' => 'camara', 'source_id' => '5001', 'title' => 'PL 1/2023']);
    $rollCall = insertRow('roll_calls', [
        'house' => 'camara', 'source_id' => '100-1', 'legislature_number' => 57, 'date' => '2023-03-01', 'organ' => 'PLEN',
        'description' => 'd', 'secret' => false, 'tally_yes' => 1, 'tally_no' => 0, 'tally_others' => 0, 'source_url' => 'https://x',
    ]);
    $membership = insertRow('memberships', [
        'member_id' => $member, 'legislature_number' => 57,
        'participation_count' => 1, 'participation_total' => 1, 'government_alignment_count' => 1, 'government_alignment_total' => 1,
        'party_alignment_count' => 1, 'party_alignment_total' => 1, 'authored_count' => 0, 'first_signer_count' => 0, 'requirements_count' => 0,
    ]);
    insertRow('votes', ['roll_call_id' => $rollCall, 'member_id' => $member, 'vote' => 'Sim', 'party' => 'P']);
    insertRow('authorships', ['membership_id' => $membership, 'proposition_id' => $proposition]);
    insertRow('contract_imports', [
        'schema_version' => 2, 'generated_at' => '2026-09-27T12:00:00Z', 'meta_sha256' => str_repeat('0', 64),
        'members_count' => 1, 'roll_calls_count' => 1, 'votes_count' => 1, 'propositions_count' => 1,
    ]);
    $skeleton = ['legislatures', 'members', 'memberships', 'propositions', 'roll_calls', 'votes', 'authorships', 'contract_imports'];
    foreach ($skeleton as $table) {
        expect(DB::table($table)->count())->toBe(1, $table);
    }

    Artisan::call('migrate', ['--force' => true]);

    foreach ($skeleton as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }
    expect(runImport(['dir' => fixtureDir('camara')])['code'])->toBe(0);
});
