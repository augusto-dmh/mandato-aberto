<?php

use App\Contract\ContractReaders;
use App\Contract\V2Reader;
use App\Import\Importer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// Checks C1-C13, C15, C16 of .specs/features/app-skeleton/checks.md.

test('imports every record of the fixture', function () {
    $result = runImport(['dir' => fixtureDir()]);

    expect($result['code'])->toBe(0);
    expect(DB::table('members')->orderBy('source_id')->get(['house', 'source_id', 'name'])->map(fn ($r) => (array) $r)->all())->toBe([
        ['house' => 'camara', 'source_id' => '101', 'name' => 'Ana Souza'],
        ['house' => 'camara', 'source_id' => '102', 'name' => 'Bruno Lima'],
        ['house' => 'camara', 'source_id' => '103', 'name' => 'Carla Dias'],
    ]);
    expect(DB::table('legislatures')->pluck('number')->all())->toBe([57]);

    $memberships = DB::table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')
        ->orderBy('members.source_id')->get(['members.source_id', 'memberships.*'])->keyBy('source_id');
    expect($memberships)->toHaveCount(3);
    foreach (readJson(fixtureDir().'/deputies.json') as $d) {
        $m = $memberships[(string) $d['id']];
        expect($m->legislature_number)->toBe(57)
            ->and([$m->participation_count, $m->participation_total])->toBe([$d['participation']['count'], $d['participation']['total']])
            ->and([$m->government_alignment_count, $m->government_alignment_total])->toBe([$d['governmentAlignment']['count'], $d['governmentAlignment']['total']])
            ->and([$m->party_alignment_count, $m->party_alignment_total])->toBe([$d['partyAlignment']['count'], $d['partyAlignment']['total']])
            ->and([$m->authored_count, $m->first_signer_count, $m->requirements_count])->toBe([$d['authoredCount'], $d['firstSignerCount'], $d['requirementsCount']]);
    }
    $ana = $memberships['101'];
    expect([$ana->participation_count, $ana->participation_total, $ana->government_alignment_count, $ana->government_alignment_total,
        $ana->party_alignment_count, $ana->party_alignment_total, $ana->authored_count, $ana->first_signer_count, $ana->requirements_count])
        ->toBe([4, 5, 2, 3, 2, 3, 5, 2, 3]);

    expect(DB::table('roll_calls')->count())->toBe(8)
        ->and(DB::table('roll_calls')->where('house', 'camara')->where('legislature_number', 57)->count())->toBe(8)
        ->and(DB::table('propositions')->count())->toBe(7)
        ->and(DB::table('propositions')->whereNotNull('title')->orderBy('source_id')->pluck('title', 'source_id')->all())->toBe(['5001' => 'PL 1/2023', '5003' => 'PEC 3/2024'])
        ->and(DB::table('authorships')->count())->toBe(6);
});

test('stores every vote of the deputy files', function () {
    runImport(['dir' => fixtureDir()]);

    $expected = [];
    foreach ([101, 102, 103] as $id) {
        foreach (readJson(fixtureDir()."/deputies/{$id}.json")['votes'] as $v) {
            $expected["{$v['rollCallId']}/{$id}"] = [$v['vote'], $v['party'], $v['partyMajority']];
        }
    }
    $stored = DB::table('votes')
        ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
        ->join('members', 'members.id', '=', 'votes.member_id')
        ->get(['roll_calls.source_id as roll_call', 'members.source_id as member', 'votes.vote', 'votes.party', 'votes.party_majority'])
        ->mapWithKeys(fn ($r) => ["{$r->roll_call}/{$r->member}" => [$r->vote, $r->party, $r->party_majority]])
        ->all();

    expect($expected)->toHaveCount(16);
    ksort($expected);
    ksort($stored);
    expect($stored)->toBe($expected);
});

test('reports and records the import', function () {
    $result = runImport(['dir' => fixtureDir()]);

    expect(trim($result['out']))->toBe('Imported schema_version 2 generated 2026-09-27T12:00:00Z: 3 members, 8 roll calls, 16 votes, 7 propositions');
    $rows = DB::table('contract_imports')->get();
    expect($rows)->toHaveCount(1);
    $row = $rows[0];
    expect($row->schema_version)->toBe(2)
        ->and(Carbon::parse($row->generated_at)->utc()->toIso8601ZuluString())->toBe('2026-09-27T12:00:00Z')
        ->and($row->meta_sha256)->toBe(hash_file('sha256', fixtureDir().'/meta.json'))
        ->and([$row->members_count, $row->roll_calls_count, $row->votes_count, $row->propositions_count])->toBe([3, 8, 16, 7]);
});

test('refuses an unsupported schema version', function () {
    runImport(['dir' => fixtureDir()]);
    $before = tableCounts();
    $dir = fixtureCopy();
    $meta = readJson("{$dir}/meta.json");
    $meta['schema_version'] = 3;
    writeJson("{$dir}/meta.json", $meta);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe('schema_version 3 is not supported; expected one of: 2')
        ->and(tableCounts())->toBe($before);
});

test('refuses a contract with a missing file', function (string $file) {
    runImport(['dir' => fixtureDir()]);
    $before = tableCounts();
    $dir = fixtureCopy();
    unlink("{$dir}/{$file}");

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("{$dir}/{$file}")
        ->and(tableCounts())->toBe($before);
})->with(['meta.json', 'deputies.json', 'roll-calls.json', 'deputies/101.json', 'roll-calls/100-1.json']);

test('refuses a file that fails its schema', function (string $file, Closure $break, string $pointer) {
    runImport(['dir' => fixtureDir()]);
    $before = tableCounts();
    $dir = fixtureCopy();
    writeJson("{$dir}/{$file}", $break(readJson("{$dir}/{$file}")));

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("{$dir}/{$file}")
        ->and($result['err'])->toContain($pointer)
        ->and(tableCounts())->toBe($before);
})->with([
    'meta' => ['meta.json', fn (array $m) => [...$m, 'generatedAt' => 'yesterday'], '/generatedAt'],
    'deputies index' => ['deputies.json', function (array $d) {
        $d[0]['uf'] = 'S';

        return $d;
    }, '/0/uf'],
    'roll-calls index' => ['roll-calls.json', function (array $r) {
        $r[0]['date'] = 'x';

        return $r;
    }, '/0/date'],
    'deputy file' => ['deputies/101.json', function (array $d) {
        $d['votes'][0]['rollCallId'] = 5;

        return $d;
    }, '/votes/0/rollCallId'],
    'roll-call file' => ['roll-calls/100-1.json', function (array $r) {
        $r['tallies']['yes'] = -1;

        return $r;
    }, '/tallies/yes'],
]);

test('rejects a missing directory', function () {
    $result = runImport(['dir' => '/nonexistent']);

    expect($result['code'])->toBe(2)
        ->and(trim($result['err']))->toBe('contract directory not found: /nonexistent');
});

test('importing twice changes nothing', function () {
    runImport(['dir' => fixtureDir()]);
    $first = tableDump(withTimestamps: false);
    runImport(['dir' => fixtureDir()]);
    $second = tableDump(withTimestamps: false);

    foreach (['members', 'memberships', 'roll_calls', 'votes', 'propositions', 'authorships'] as $table) {
        expect($first[$table])->not->toBeEmpty()
            ->and($second[$table])->toBe($first[$table]);
    }
});

/** A copy of the fixture without roll call 200-3, deputy 103, 102's vote on 100-4 and 101's authorship of 6005. */
function reducedFixture(): string
{
    $dir = fixtureCopy();

    $rollCalls = array_values(array_filter(readJson("{$dir}/roll-calls.json"), fn ($r) => $r['id'] !== '200-3'));
    writeJson("{$dir}/roll-calls.json", $rollCalls);
    unlink("{$dir}/roll-calls/200-3.json");
    foreach (glob("{$dir}/roll-calls/*.json") as $path) {
        $r = readJson($path);
        $r['votes'] = array_values(array_filter($r['votes'], fn ($v) => $v['deputyId'] !== 103 && ! ($r['id'] === '100-4' && $v['deputyId'] === 102)));
        writeJson($path, $r);
    }

    writeJson("{$dir}/deputies.json", array_values(array_filter(readJson("{$dir}/deputies.json"), fn ($d) => $d['id'] !== 103)));
    unlink("{$dir}/deputies/103.json");

    $ana = readJson("{$dir}/deputies/101.json");
    $ana['votes'] = array_values(array_filter($ana['votes'], fn ($v) => $v['rollCallId'] !== '200-3'));
    $ana['authored'] = array_values(array_filter($ana['authored'], fn ($a) => $a['id'] !== 6005));
    writeJson("{$dir}/deputies/101.json", $ana);

    $bruno = readJson("{$dir}/deputies/102.json");
    $bruno['votes'] = array_values(array_filter($bruno['votes'], fn ($v) => $v['rollCallId'] !== '100-4'));
    writeJson("{$dir}/deputies/102.json", $bruno);

    return $dir;
}

function voteExists(string $rollCall, string $member): bool
{
    return DB::table('votes')
        ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
        ->join('members', 'members.id', '=', 'votes.member_id')
        ->where('roll_calls.source_id', $rollCall)->where('members.source_id', $member)->exists();
}

test('sweeps what the snapshot no longer has', function () {
    runImport(['dir' => fixtureDir()]);
    expect(voteExists('100-4', '102'))->toBeTrue();

    $result = runImport(['dir' => reducedFixture()]);

    expect($result['code'])->toBe(0);
    $member103 = DB::table('members')->where('source_id', '103')->value('id');
    expect(DB::table('roll_calls')->where('source_id', '200-3')->exists())->toBeFalse()
        ->and(voteExists('200-3', '101'))->toBeFalse()
        ->and(DB::table('memberships')->where('member_id', $member103)->exists())->toBeFalse()
        ->and(DB::table('votes')->where('member_id', $member103)->exists())->toBeFalse()
        ->and(voteExists('100-4', '102'))->toBeFalse()
        ->and(DB::table('authorships')
            ->join('memberships', 'memberships.id', '=', 'authorships.membership_id')
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->join('propositions', 'propositions.id', '=', 'authorships.proposition_id')
            ->where('members.source_id', '101')->where('propositions.source_id', '6005')->exists())->toBeFalse()
        ->and($member103)->not->toBeNull()
        ->and(DB::table('propositions')->where('source_id', '6005')->exists())->toBeTrue()
        // what stayed in the snapshot stays in the database
        ->and(voteExists('100-4', '101'))->toBeTrue()
        ->and(DB::table('votes')->count())->toBe(16 - 1 - 3 - 1);
});

test('rolls back on a failed write', function () {
    runImport(['dir' => fixtureDir()]);
    $before = tableDump();

    $dir = fixtureCopy();
    $deputies = readJson("{$dir}/deputies.json");
    $deputies[0]['name'] = 'Ana Souza Lima';
    writeJson("{$dir}/deputies.json", $deputies);
    $ana = readJson("{$dir}/deputies/101.json");
    foreach ($ana['votes'] as &$vote) {
        if ($vote['rollCallId'] === '100-1') {
            $vote['vote'] = 'Não';
        }
    }
    writeJson("{$dir}/deputies/101.json", $ana);

    DB::unprepared(<<<'SQL'
        create function refuse_authorship() returns trigger language plpgsql as $$
        begin raise exception 'injected failure'; end $$;
        create trigger refuse_authorship before insert or update on authorships
        for each row execute function refuse_authorship();
    SQL);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and(tableDump())->toBe($before);
});

test('refuses while another import holds the lock', function () {
    config(['database.connections.second' => config('database.connections.pgsql')]);
    $second = DB::connection('second');
    $second->select('select pg_advisory_lock(?)', [Importer::LOCK_KEY]);

    try {
        $result = runImport(['dir' => fixtureDir()]);
    } finally {
        $second->select('select pg_advisory_unlock(?)', [Importer::LOCK_KEY]);
        $second->disconnect();
    }

    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe('another import is running')
        ->and(array_sum(tableCounts()))->toBe(0);
});

test('dry run validates and writes nothing', function () {
    $result = runImport(['dir' => fixtureDir(), '--dry-run' => true]);

    expect($result['code'])->toBe(0)
        ->and(trim($result['out']))->toBe('Would import schema_version 2 generated 2026-09-27T12:00:00Z: 3 members, 8 roll calls, 16 votes, 7 propositions')
        ->and(array_sum(tableCounts()))->toBe(0);

    $dir = fixtureCopy();
    $meta = readJson("{$dir}/meta.json");
    $meta['schema_version'] = 3;
    writeJson("{$dir}/meta.json", $meta);
    $refused = runImport(['dir' => $dir, '--dry-run' => true]);

    expect($refused['code'])->toBe(1)
        ->and(array_sum(tableCounts()))->toBe(0);
});

test('persists no candidacy field and no cpf', function () {
    runImport(['dir' => fixtureDir()]);

    foreach (MANDATE_TABLES as $table) {
        foreach (Schema::getColumnListing($table) as $column) {
            expect($column)->not->toMatch('/cpf|candidacy|office|ballot|situation/i');
        }
    }
    $dump = json_encode(tableDump(), JSON_UNESCAPED_UNICODE);
    foreach (['DEPUTADO FEDERAL', 'SENADOR', '1313', 'APTO', 'consulta_cand_2026_BRASIL.csv'] as $value) {
        expect($dump)->not->toContain($value);
    }
});

test('resolves one reader per supported version', function () {
    expect(ContractReaders::SUPPORTED_SCHEMA_VERSIONS)->toBe([2])
        ->and(ContractReaders::for(2))->toBeInstanceOf(V2Reader::class)
        ->and(ContractReaders::for(1))->toBeNull()
        ->and(ContractReaders::for(3))->toBeNull();
});

test('defaults the contract directory', function () {
    expect(config('mandato.contract_dir'))->toBe(base_path('../data/out'));
});

test('validates against the schema directory', function () {
    expect(config('mandato.schema_dir'))->toBe(base_path('../etl/schema'));

    $schemas = sys_get_temp_dir().'/mandato-schema-'.bin2hex(random_bytes(6));
    File::copyDirectory(base_path('../etl/schema'), $schemas);
    $deputies = readJson("{$schemas}/deputies.schema.json");
    $deputies['$defs']['deputy']['properties']['uf']['pattern'] = '^ZZ$';
    writeJson("{$schemas}/deputies.schema.json", $deputies);
    config(['mandato.schema_dir' => $schemas]);

    $result = runImport(['dir' => fixtureDir()]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain('deputies.json')
        ->and($result['err'])->toContain('/0/uf');
});
