<?php

use App\Contract\ContractReaders;
use App\Contract\JsonFiles;
use App\Contract\V3Reader;
use App\Import\Importer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// Checks C1-C28 of .specs/features/app-contract-v3/checks.md.

/**
 * Every row that belongs to one house, across the contract tables, by natural key.
 *
 * @return array<string, list<array<string, mixed>>>
 */
function houseDump(string $house, bool $timestamps = true): array
{
    $members = DB::table('members')->where('house', $house)->pluck('id');
    $memberships = DB::table('memberships')->whereIn('member_id', $members)->pluck('id');
    $rollCalls = DB::table('roll_calls')->where('house', $house)->pluck('id');
    $propositions = DB::table('propositions')->where('house', $house)->pluck('id');
    $row = function ($r) use ($timestamps) {
        $r = (array) $r;
        if (! $timestamps) {
            unset($r['created_at'], $r['updated_at']);
        }

        return $r;
    };
    $get = fn (string $table, string $column, $ids) => DB::table($table)->whereIn($column, $ids)->orderBy('id')->get()->map($row)->all();

    return [
        'members' => $get('members', 'id', $members),
        'memberships' => $get('memberships', 'id', $memberships),
        'exercise_periods' => $get('exercise_periods', 'membership_id', $memberships),
        'roll_calls' => $get('roll_calls', 'id', $rollCalls),
        'votes' => $get('votes', 'roll_call_id', $rollCalls),
        'propositions' => $get('propositions', 'id', $propositions),
        'authorships' => $get('authorships', 'membership_id', $memberships),
        'classification_rules' => DB::table('classification_rules')->where('house', $house)->orderBy('id')->get()->map($row)->all(),
        'full_texts' => $get('full_texts', 'proposition_id', $propositions),
    ];
}

function membershipOf(string $house, string $member, int $legislature): ?object
{
    return DB::table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')
        ->where('members.house', $house)->where('members.source_id', $member)->where('memberships.legislature_number', $legislature)
        ->first(['memberships.*']);
}

function voteOf(string $house, string $rollCall, string $member): ?object
{
    return DB::table('votes')
        ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
        ->join('members', 'members.id', '=', 'votes.member_id')
        ->where('roll_calls.house', $house)->where('roll_calls.source_id', $rollCall)->where('members.source_id', $member)
        ->first(['votes.*']);
}

/** Changes one JSON file of a copy in place. */
function editJson(string $path, Closure $change): void
{
    writeJson($path, $change(readJson($path)));
}

const CAMARA_LINE = 'schema_version 3 camara generated 2027-03-02T02:30:00Z: 3 members, 4 mandates, 8 roll calls, 13 votes, 5 propositions, 1 full texts';
const SENADO_LINE = 'schema_version 3 senado generated 2027-03-05T12:00:00Z: 6 members, 6 mandates, 2 roll calls, 12 votes, 1 propositions, 0 full texts';

test('imports the members and mandates of a house', function () {
    $result = runImport(['dir' => fixtureDir('camara')]);

    expect($result['code'])->toBe(0);
    $contract = readJson(fixtureDir('camara').'/members.json');
    expect(DB::table('members')->where('house', 'camara')->orderBy('source_id')->pluck('source_id')->all())->toBe(['101', '102', '103'])
        ->and(DB::table('memberships')->count())->toBe(4);

    $mandates = 0;
    foreach ($contract as $m) {
        $member = DB::table('members')->where('house', 'camara')->where('source_id', (string) $m['id'])->first();
        expect([$member->name, $member->party, $member->uf, $member->photo_url, $member->source_url])
            ->toBe([$m['name'], $m['party'], $m['uf'], $m['photoUrl'], $m['sourceUrl']]);
        foreach ($m['mandates'] as $mandate) {
            $mandates++;
            $row = membershipOf('camara', (string) $m['id'], $mandate['legislature']);
            expect($row)->not->toBeNull()
                ->and([$row->party, $row->uf])->toBe([$mandate['party'], $mandate['uf']]);
            foreach (['participation' => 'participation', 'governmentAlignment' => 'government_alignment', 'partyAlignment' => 'party_alignment'] as $key => $column) {
                foreach (['all', 'merit'] as $basis) {
                    expect([$row->{"{$column}_{$basis}_count"}, $row->{"{$column}_{$basis}_total"}])
                        ->toBe([$mandate[$key][$basis]['count'], $mandate[$key][$basis]['total']]);
                }
            }
            expect([$row->symbolic_merit, $row->authored_count, $row->first_signer_count, $row->requirements_count])
                ->toBe([$mandate['symbolicMerit'], $mandate['authoredCount'], $mandate['firstSignerCount'], $mandate['requirementsCount']]);
        }
    }
    expect($mandates)->toBe(4);

    $parties = collect([['101', 57], ['101', 58], ['102', 57], ['103', 57]])
        ->map(fn ($k) => (fn ($r) => "{$r->party} {$r->uf}")(membershipOf('camara', $k[0], $k[1])))->all();
    expect($parties)->toBe(['PSB SP', 'PT SP', 'PL RJ', 'MDB MG']);
    $ana57 = membershipOf('camara', '101', 57);
    expect([$ana57->participation_merit_count, $ana57->participation_merit_total, $ana57->participation_all_count, $ana57->participation_all_total])
        ->toBe([3, 4, 7, 10]);
});

test('stores each exercise period of a mandate', function () {
    runImport(['dir' => fixtureDir('camara')]);

    $periods = DB::table('exercise_periods')
        ->join('memberships', 'memberships.id', '=', 'exercise_periods.membership_id')
        ->join('members', 'members.id', '=', 'memberships.member_id')
        ->orderBy('members.source_id')->orderBy('memberships.legislature_number')->orderBy('exercise_periods.starts_at')
        ->get(['members.source_id', 'memberships.legislature_number', 'exercise_periods.starts_at', 'exercise_periods.ends_at'])
        ->map(fn ($p) => "{$p->source_id} {$p->legislature_number} {$p->starts_at}..{$p->ends_at}")->all();

    expect($periods)->toBe([
        '101 57 2023-02-01 00:00:00..2024-06-01 00:00:00',
        '101 57 2024-09-01 00:00:00..2027-02-01 00:00:00',
        '101 58 2027-02-01 00:00:00..2027-03-01 09:00:00',
        '102 57 2023-02-01 00:00:00..2027-02-01 00:00:00',
        '103 57 2023-02-01 00:00:00..2027-02-01 00:00:00',
    ]);
});

test('imports every roll call with ballot and kind', function () {
    runImport(['dir' => fixtureDir('camara')]);

    $contract = readJson(fixtureDir('camara').'/roll-calls.json');
    expect(DB::table('roll_calls')->count())->toBe(8)->and($contract)->toHaveCount(8);
    foreach ($contract as $r) {
        $row = DB::table('roll_calls')->where('house', 'camara')->where('source_id', $r['id'])->first();
        expect([$row->legislature_number, $row->date, $row->organ, $row->description, $row->approved, $row->ballot, $row->kind, $row->kind_rule,
            $row->tally_yes, $row->tally_no, $row->tally_others, $row->government_orientation, $row->source_url])
            ->toBe([$r['legislature'], $r['date'], $r['organ'], $r['description'], $r['approved'], $r['ballot'], $r['kind'], $r['kindRule'],
                $r['tallies']['yes'] ?? null, $r['tallies']['no'] ?? null, $r['tallies']['others'] ?? null, $r['governmentOrientation'], $r['sourceUrl']], $r['id']);
        $file = fixtureDir('camara')."/roll-calls/{$r['id']}.json";
        $detail = is_file($file) ? readJson($file) : ['openingDescription' => null, 'lastPresentationDescription' => null];
        expect([$row->opening_description, $row->last_presentation_description])->toBe([$detail['openingDescription'], $detail['lastPresentationDescription']]);
    }

    $described = DB::table('roll_calls')->pluck('opening_description', 'source_id');
    expect($described['100-5'])->toBe('Votação secreta em turno único.')
        ->and(DB::table('roll_calls')->where('source_id', '100-1')->value('last_presentation_description'))->toBe('Apresentação do Projeto de Lei n. 1/2023')
        ->and((array) DB::table('roll_calls')->where('source_id', '100-4')->first(['opening_description', 'last_presentation_description']))
        ->toBe(['opening_description' => null, 'last_presentation_description' => null]);
});

test('imports every vote with official and position', function () {
    runImport(['dir' => fixtureDir('camara')]);

    $expected = [];
    foreach (glob(fixtureDir('camara').'/roll-calls/*.json') as $path) {
        $r = readJson($path);
        foreach ($r['votes'] as $v) {
            $expected["{$r['id']}/{$v['memberId']}"] = [$v['official'], $v['position'], $v['party'], $v['partyMajority']];
        }
    }
    $stored = DB::table('votes')
        ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
        ->join('members', 'members.id', '=', 'votes.member_id')
        ->get(['roll_calls.source_id as roll_call', 'members.source_id as member', 'votes.official', 'votes.position', 'votes.party', 'votes.party_majority'])
        ->mapWithKeys(fn ($r) => ["{$r->roll_call}/{$r->member}" => [$r->official, $r->position, $r->party, $r->party_majority]])
        ->all();

    expect($expected)->toHaveCount(13);
    ksort($expected);
    ksort($stored);
    expect($stored)->toBe($expected)
        ->and($stored['100-2/103'])->toBe(['', 'notVoting', 'MDB', null])
        ->and($stored['100-6/101'])->toBe(['', 'secret', 'PSB', null])
        ->and($stored['100-1/103'])->toBe(['Artigo 17', 'presiding', 'MDB', null]);
});

test('imports propositions rules and full texts', function () {
    runImport(['dir' => fixtureDir('camara')]);

    $contract = readJson(fixtureDir('camara').'/propositions.json');
    expect(DB::table('propositions')->count())->toBe(5);
    foreach ($contract as $p) {
        $row = DB::table('propositions')->where('house', 'camara')->where('source_id', (string) $p['id'])->first();
        expect([$row->type, $row->number, $row->year, $row->summary, $row->presented_on, $row->status, $row->source_url])
            ->toBe([$p['type'], $p['number'], $p['year'], $p['summary'], $p['presentedAt'], $p['status'], $p['sourceUrl']]);
    }
    $req = DB::table('propositions')->where('source_id', '5005')->first();
    expect([$req->type, $req->number, $req->year])->toBe(['REQ', 5, 2023])
        ->and(DB::table('propositions')->where('source_id', '5007')->value('presented_on'))->toBeNull();

    $rules = readJson(fixtureDir('camara').'/classification-rules.json');
    $stored = DB::table('classification_rules')->where('house', 'camara')->orderBy('position')->get();
    expect($stored)->toHaveCount(11)
        ->and($stored->pluck('rule_id')->all())->toBe(array_map(fn ($i) => sprintf('camara.%02d', $i), range(1, 11)));
    foreach ($rules as $i => $r) {
        expect([$stored[$i]->rule_id, $stored[$i]->kind, $stored[$i]->field, $stored[$i]->pattern, $stored[$i]->description])
            ->toBe([$r['id'], $r['kind'], $r['field'], $r['pattern'], $r['description']]);
    }

    $text = readJson(fixtureDir('camara').'/full-texts/5001.json');
    $rows = DB::table('full_texts')->join('propositions', 'propositions.id', '=', 'full_texts.proposition_id')
        ->get(['propositions.source_id', 'full_texts.*']);
    expect($rows)->toHaveCount(1);
    $row = $rows[0];
    expect([$row->source_id, $row->source_url, $row->document_sha256, $row->extractor, Carbon::parse($row->extracted_at)->utc()->toIso8601ZuluString(), $row->text])
        ->toBe(['5001', $text['sourceUrl'], $text['documentSha256'], $text['extractor'], $text['extractedAt'], $text['text']]);
});

test('attaches authorship to the mandate of the presentation date', function () {
    runImport(['dir' => fixtureDir('camara')]);

    $authorships = DB::table('authorships')
        ->join('memberships', 'memberships.id', '=', 'authorships.membership_id')
        ->join('members', 'members.id', '=', 'memberships.member_id')
        ->join('propositions', 'propositions.id', '=', 'authorships.proposition_id')
        ->orderBy('propositions.source_id')->orderBy('members.source_id')
        ->get(['members.source_id as member', 'memberships.legislature_number', 'propositions.source_id as proposition', 'authorships.first_signer'])
        ->map(fn ($a) => [$a->member, $a->legislature_number, $a->proposition, $a->first_signer])->all();

    expect($authorships)->toBe([
        ['101', 57, '5001', true],
        ['102', 57, '5001', false],
        ['103', 57, '5003', true],
        ['101', 58, '5006', true],
    ]);
});

test('keeps absent counts null and never zero', function () {
    importFixtures();

    $senate = DB::table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')->where('members.house', 'senado')->pluck('symbolic_merit');
    expect($senate)->toHaveCount(6)
        ->and($senate->filter(fn ($v) => $v !== null)->all())->toBe([])
        ->and(membershipOf('camara', '102', 57)->symbolic_merit)->toBe(1)
        ->and(membershipOf('camara', '101', 58)->symbolic_merit)->toBe(0);

    $tallies = fn (string $id) => array_values((array) DB::table('roll_calls')->where('house', 'camara')->where('source_id', $id)->first(['tally_yes', 'tally_no', 'tally_others']));
    expect($tallies('100-4'))->toBe([null, null, null])
        ->and($tallies('100-5'))->toBe([null, null, null])
        ->and($tallies('100-6'))->toBe([2, 1, 0]);
});

test('reports and records one import per house', function () {
    $camara = runImport(['dir' => fixtureDir('camara')]);
    $senado = runImport(['dir' => fixtureDir('senado')]);

    expect(trim($camara['out']))->toBe('Imported '.CAMARA_LINE)
        ->and(trim($senado['out']))->toBe('Imported '.SENADO_LINE);
    $rows = DB::table('contract_imports')->orderBy('id')->get();
    expect($rows)->toHaveCount(2);
    $row = $rows[0];
    $meta = readJson(fixtureDir('camara').'/meta.json');
    expect($row->house)->toBe('camara')
        ->and($row->schema_version)->toBe(3)
        ->and(Carbon::parse($row->generated_at)->utc()->toIso8601ZuluString())->toBe('2027-03-02T02:30:00Z')
        ->and($row->meta_sha256)->toBe(hash_file('sha256', fixtureDir('camara').'/meta.json'))
        ->and($row->classification_version)->toBe(1)
        ->and(json_decode($row->coverage, true))->toEqual($meta['coverage'])
        ->and([$row->members_count, $row->mandates_count, $row->roll_calls_count, $row->votes_count, $row->propositions_count, $row->full_texts_count])
        ->toBe([3, 4, 8, 13, 5, 1])
        ->and($rows[1]->house)->toBe('senado');
});

test('refuses a schema version other than 3', function (int $version) {
    importFixtures();
    $before = tableCounts();
    $dir = fixtureCopy('camara');
    editJson("{$dir}/meta.json", fn ($m) => [...$m, 'schema_version' => $version]);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe("schema_version {$version} is not supported; expected one of: 3")
        ->and(tableCounts())->toBe($before);
})->with([2, 4]);

test('refuses a house with a missing file', function (string $file) {
    importFixtures();
    $before = houseDump('camara');
    $dir = fixtureCopy('camara');
    unlink("{$dir}/{$file}");

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("{$dir}/{$file}")
        ->and(houseDump('camara'))->toBe($before);
})->with(['members.json', 'roll-calls.json', 'propositions.json', 'classification-rules.json', 'roll-calls/100-1.json']);

test('refuses a house file that fails its v3 schema', function (string $file, Closure $break, string $pointer) {
    importFixtures();
    $before = houseDump('camara');
    $dir = fixtureCopy('camara');
    editJson("{$dir}/{$file}", $break);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("{$dir}/{$file}")
        ->and($result['err'])->toContain($pointer)
        ->and(houseDump('camara'))->toBe($before);
})->with([
    'meta' => ['meta.json', fn ($m) => [...$m, 'generatedAt' => 'yesterday'], '/generatedAt'],
    'members' => ['members.json', function ($m) {
        $m[0]['mandates'][0]['participation']['merit']['total'] = -1;

        return $m;
    }, '/0/mandates/0/participation/merit/total'],
    'roll-calls index' => ['roll-calls.json', function ($r) {
        $r[0]['ballot'] = 'open';

        return $r;
    }, '/0/ballot'],
    'propositions' => ['propositions.json', function ($p) {
        $p[0]['presentedAt'] = 'x';

        return $p;
    }, '/0/presentedAt'],
    'rules' => ['classification-rules.json', function ($r) {
        $r[0]['kind'] = 'important';

        return $r;
    }, '/0/kind'],
    'roll-call file' => ['roll-calls/100-1.json', function ($r) {
        $r['votes'][0]['position'] = 'other';

        return $r;
    }, '/votes/0/position'],
    'full text' => ['full-texts/5001.json', fn ($t) => [...$t, 'documentSha256' => 'xyz'], '/documentSha256'],
]);

test('refuses a raw senate leave code', function (string $code) {
    $dir = fixtureCopy('senado');
    editJson("{$dir}/roll-calls/6923.json", function ($r) use ($code) {
        $r['votes'][0]['official'] = $code;
        $r['votes'][0]['position'] = 'notVoting';

        return $r;
    });

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain('roll-calls/6923.json')
        ->and($result['err'])->toContain('/votes/0/official')
        ->and(DB::table('votes')->whereIn('official', ['LS', 'LP', 'LAP'])->count())->toBe(0)
        ->and(DB::table('votes')->count())->toBe(0);
})->with(['LS', 'LP', 'LAP']);

test('refuses a house whose references do not resolve', function (string $house, Closure $break, string $file, string $id) {
    importFixtures();
    $before = houseDump($house);
    $dir = fixtureCopy($house);
    $break($dir);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toMatch('/'.preg_quote("{$dir}/{$file}: ", '/').'.+: '.preg_quote($id, '/').'\s*$/')
        ->and(houseDump($house))->toBe($before);
})->with([
    'vote member' => ['senado', fn ($d) => editJson("{$d}/roll-calls/6923.json", function ($r) {
        $r['votes'][0]['memberId'] = 9999;

        return $r;
    }), 'roll-calls/6923.json', '9999'],
    'author member' => ['camara', fn ($d) => editJson("{$d}/propositions.json", function ($p) {
        $p[0]['authors'][0]['memberId'] = 9999;

        return $p;
    }), 'propositions.json', '9999'],
    'nominal without file' => ['camara', fn ($d) => unlink("{$d}/roll-calls/100-1.json"), 'roll-calls/100-1.json', '100-1'],
    'file for a symbolic roll call' => ['camara', fn ($d) => writeJson("{$d}/roll-calls/100-4.json", [
        ...readJson("{$d}/roll-calls/100-1.json"), 'id' => '100-4',
    ]), 'roll-calls/100-4.json', '100-4'],
    'file for an unknown roll call' => ['camara', fn ($d) => writeJson("{$d}/roll-calls/555-5.json", [
        ...readJson("{$d}/roll-calls/100-1.json"), 'id' => '555-5',
    ]), 'roll-calls/555-5.json', '555-5'],
    'kindRule' => ['camara', fn ($d) => editJson("{$d}/roll-calls.json", fn ($rs) => array_map(fn ($r) => $r['id'] === '100-1' ? [...$r, 'kindRule' => 'camara.99'] : $r, $rs)),
        'roll-calls.json', '100-1'],
    'member house' => ['camara', fn ($d) => editJson("{$d}/members.json", function ($m) {
        $m[0]['house'] = 'senado';

        return $m;
    }), 'members.json', '101'],
    'roll call house' => ['camara', fn ($d) => editJson("{$d}/roll-calls.json", fn ($rs) => array_map(fn ($r) => $r['id'] === '100-1' ? [...$r, 'house' => 'senado'] : $r, $rs)),
        'roll-calls.json', '100-1'],
    'proposition house' => ['camara', fn ($d) => editJson("{$d}/propositions.json", fn ($ps) => array_map(fn ($p) => $p['id'] === 5001 ? [...$p, 'house' => 'senado'] : $p, $ps)),
        'propositions.json', '5001'],
    'rule house' => ['camara', fn ($d) => editJson("{$d}/classification-rules.json", function ($r) {
        $r[0]['house'] = 'senado';

        return $r;
    }), 'classification-rules.json', 'camara.01'],
    'roll-call file house' => ['camara', fn ($d) => editJson("{$d}/roll-calls/100-1.json", fn ($r) => [...$r, 'house' => 'senado']), 'roll-calls/100-1.json', '100-1'],
    'full text house' => ['camara', fn ($d) => editJson("{$d}/full-texts/5001.json", fn ($t) => [...$t, 'house' => 'senado']), 'full-texts/5001.json', '5001'],
    'roll call legislature' => ['camara', fn ($d) => editJson("{$d}/roll-calls.json", fn ($rs) => array_map(fn ($r) => $r['id'] === '100-1' ? [...$r, 'legislature' => 59] : $r, $rs)),
        'roll-calls.json', '100-1'],
    'mandate legislature' => ['camara', fn ($d) => editJson("{$d}/members.json", function ($m) {
        $m[0]['mandates'][0]['legislature'] = 59;

        return $m;
    }), 'members.json', '101'],
    'full text proposition' => ['camara', fn ($d) => writeJson("{$d}/full-texts/9999.json", [
        ...readJson("{$d}/full-texts/5001.json"), 'propositionId' => 9999,
    ]), 'full-texts/9999.json', '9999'],
    'roll call proposition' => ['camara', fn ($d) => editJson("{$d}/roll-calls.json", fn ($rs) => array_map(fn ($r) => $r['id'] === '100-1' ? [...$r, 'propositionId' => 4242] : $r, $rs)),
        'roll-calls.json', '100-1'],
]);

test('refuses legislature dates that differ from the stored ones', function () {
    runImport(['dir' => fixtureDir('camara')]);
    $dir = fixtureCopy('senado');
    editJson("{$dir}/meta.json", function ($m) {
        $m['legislatures'][0]['start'] = '2023-02-02';

        return $m;
    });
    $line = 'legislature 57 dates differ: stored 2023-02-01..2027-01-31, contract 2023-02-02..2027-01-31';

    $result = runImport(['dir' => $dir]);
    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe($line)
        ->and(houseDump('senado'))->toBe(houseDump('nobody'))
        ->and(DB::table('members')->where('house', 'senado')->count())->toBe(0);

    $dry = runImport(['dir' => $dir, '--dry-run' => true]);
    expect($dry['code'])->toBe(1)
        ->and(trim($dry['err']))->toBe($line);
});

test('importing a house twice changes nothing', function () {
    importFixtures();
    $first = tableDump(withTimestamps: false);
    importFixtures();
    $second = tableDump(withTimestamps: false);

    foreach (['members', 'memberships', 'exercise_periods', 'roll_calls', 'votes', 'propositions', 'authorships', 'classification_rules', 'full_texts'] as $table) {
        expect($first[$table])->not->toBeEmpty()
            ->and($second[$table])->toBe($first[$table], $table);
    }
});

test('sweeps each scope the house lists', function () {
    runImport(['dir' => fixtureDir('camara')]);
    expect(voteOf('camara', '100-1', '102'))->not->toBeNull();
    $dir = fixtureCopy('camara');

    editJson("{$dir}/roll-calls.json", fn ($rs) => array_values(array_filter($rs, fn ($r) => $r['id'] !== '100-3')));
    unlink("{$dir}/roll-calls/100-3.json");
    foreach (glob("{$dir}/roll-calls/*.json") as $path) {
        editJson($path, function ($r) {
            $r['votes'] = array_values(array_filter($r['votes'], fn ($v) => $v['memberId'] !== 103 && ! ($r['id'] === '100-1' && $v['memberId'] === 102)));

            return $r;
        });
    }
    editJson("{$dir}/members.json", fn ($ms) => array_map(function ($m) {
        if ($m['id'] === 103) {
            $m['mandates'] = [];
        }
        if ($m['id'] === 101) {
            $m['mandates'][0]['exercisePeriods'] = [$m['mandates'][0]['exercisePeriods'][0]];
        }

        return $m;
    }, $ms));
    editJson("{$dir}/propositions.json", fn ($ps) => array_map(function ($p) {
        if ($p['id'] === 5001) {
            $p['authors'] = array_values(array_filter($p['authors'], fn ($a) => $a['memberId'] !== 102));
        }

        return $p;
    }, $ps));

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(0, $result['err']);
    $member103 = DB::table('members')->where('house', 'camara')->where('source_id', '103')->value('id');
    $ana57 = membershipOf('camara', '101', 57);
    $bruno57 = membershipOf('camara', '102', 57);
    $p5001 = DB::table('propositions')->where('source_id', '5001')->value('id');
    expect(DB::table('roll_calls')->where('source_id', '100-3')->exists())->toBeFalse()
        ->and(voteOf('camara', '100-3', '101'))->toBeNull()
        ->and(voteOf('camara', '100-3', '102'))->toBeNull()
        ->and(voteOf('camara', '100-1', '102'))->toBeNull()
        ->and(membershipOf('camara', '103', 57))->toBeNull()
        ->and(DB::table('votes')->where('member_id', $member103)->exists())->toBeFalse()
        ->and(DB::table('exercise_periods')->where('membership_id', $ana57->id)->count())->toBe(1)
        ->and(DB::table('authorships')->where('membership_id', $bruno57->id)->where('proposition_id', $p5001)->exists())->toBeFalse()
        ->and($member103)->not->toBeNull()
        ->and($p5001)->not->toBeNull()
        ->and(voteOf('camara', '100-1', '101'))->not->toBeNull()
        ->and(DB::table('exercise_periods')->count())->toBe(3);
});

test('leaves a legislature the house does not list', function () {
    runImport(['dir' => fixtureDir('camara')]);
    $state = function () {
        $ana58 = membershipOf('camara', '101', 58);

        return [
            'membership' => (array) $ana58,
            'periods' => DB::table('exercise_periods')->where('membership_id', $ana58->id)->get()->map(fn ($r) => (array) $r)->all(),
            'roll call' => (array) DB::table('roll_calls')->where('source_id', '300-1')->first(),
            'vote' => (array) voteOf('camara', '300-1', '101'),
            'authorship' => DB::table('authorships')->where('membership_id', $ana58->id)->get()->map(fn ($r) => (array) $r)->all(),
        ];
    };
    $before = $state();
    expect($before['authorship'])->toHaveCount(1);

    $dir = fixtureCopy('camara');
    editJson("{$dir}/meta.json", function ($m) {
        $m['legislatures'] = [$m['legislatures'][0]];
        $m['coverage'] = [$m['coverage'][0]];

        return $m;
    });
    editJson("{$dir}/members.json", fn ($ms) => array_map(function ($m) {
        $m['mandates'] = array_values(array_filter($m['mandates'], fn ($x) => $x['legislature'] !== 58));

        return $m;
    }, $ms));
    editJson("{$dir}/roll-calls.json", fn ($rs) => array_values(array_filter($rs, fn ($r) => $r['id'] !== '300-1')));
    unlink("{$dir}/roll-calls/300-1.json");

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(0, $result['err'])
        ->and($state())->toBe($before);
});

test("replaces a house's rules and full texts as a set", function () {
    importFixtures();
    $senate = houseDump('senado');
    unset($senate['full_texts']);
    $dir = fixtureCopy('camara');
    editJson("{$dir}/classification-rules.json", fn ($rs) => array_values(array_filter(array_reverse($rs), fn ($r) => $r['id'] !== 'camara.06')));
    unlink("{$dir}/full-texts/5001.json");
    writeJson("{$dir}/full-texts/5003.json", [...readJson(fixtureDir('camara').'/full-texts/5001.json'), 'propositionId' => 5003, 'text' => 'Art. 37.']);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(0, $result['err']);
    $copy = array_map(fn ($r) => $r['id'], readJson("{$dir}/classification-rules.json"));
    expect($copy)->toHaveCount(10)
        ->and(DB::table('classification_rules')->where('house', 'camara')->orderBy('position')->pluck('rule_id')->all())->toBe($copy)
        ->and($copy[0])->toBe('camara.11')
        ->and(DB::table('full_texts')->join('propositions', 'propositions.id', '=', 'full_texts.proposition_id')->pluck('propositions.source_id')->all())->toBe(['5003']);
    $after = houseDump('senado');
    unset($after['full_texts']);
    expect($after)->toBe($senate);
});

test('imports camara then senado from a parent directory', function () {
    $result = runImport(['dir' => fixtureDir()]);

    expect($result['code'])->toBe(0)
        ->and(explode("\n", trim($result['out'])))->toBe(['Imported '.CAMARA_LINE, 'Imported '.SENADO_LINE])
        ->and(DB::table('contract_imports')->orderBy('id')->pluck('house')->all())->toBe(['camara', 'senado']);

    DB::table('contract_imports')->delete();
    $parent = sys_get_temp_dir().'/mandato-parent-'.bin2hex(random_bytes(6));
    File::copyDirectory(fixtureDir('camara'), "{$parent}/camara");
    $only = runImport(['dir' => $parent]);

    expect($only['code'])->toBe(0)
        ->and(trim($only['out']))->toBe('Imported '.CAMARA_LINE)
        ->and(DB::table('contract_imports')->pluck('house')->all())->toBe(['camara']);
});

test('keeps the other house when one house fails', function () {
    $parent = fixtureCopy();
    editJson("{$parent}/senado/roll-calls/6923.json", function ($r) {
        $r['votes'][0]['memberId'] = 9999;

        return $r;
    });

    $result = runImport(['dir' => $parent]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("{$parent}/senado")
        ->and($result['err'])->toContain('9999')
        ->and(DB::table('members')->where('house', 'camara')->count())->toBe(3)
        ->and(DB::table('memberships')->count())->toBe(4)
        ->and(DB::table('roll_calls')->where('house', 'camara')->count())->toBe(8)
        ->and(DB::table('votes')->count())->toBe(13)
        ->and(DB::table('authorships')->count())->toBe(4)
        ->and(DB::table('contract_imports')->pluck('house')->all())->toBe(['camara'])
        ->and(DB::table('members')->where('house', 'senado')->count())->toBe(0)
        ->and(DB::table('roll_calls')->where('house', 'senado')->count())->toBe(0)
        ->and(DB::table('classification_rules')->where('house', 'senado')->count())->toBe(0);

    DB::table('contract_imports')->delete();
    importFixtures();
    $before = [houseDump('camara', timestamps: false), houseDump('senado', timestamps: false)];
    $again = runImport(['dir' => $parent]);
    expect($again['code'])->toBe(1)
        ->and([houseDump('camara', timestamps: false), houseDump('senado', timestamps: false)])->toBe($before);

    // The Câmara refused first: the Senate still imports.
    foreach (['votes', 'authorships', 'exercise_periods', 'memberships', 'roll_calls', 'full_texts', 'classification_rules', 'propositions', 'members', 'contract_imports'] as $table) {
        DB::table($table)->delete();
    }
    $broken = fixtureCopy();
    editJson("{$broken}/camara/meta.json", fn ($m) => [...$m, 'schema_version' => 2]);

    $reverse = runImport(['dir' => $broken]);

    expect($reverse['code'])->toBe(1)
        ->and($reverse['err'])->toContain("{$broken}/camara")
        ->and($reverse['err'])->toContain('schema_version 2 is not supported')
        ->and(trim($reverse['out']))->toBe('Imported '.SENADO_LINE)
        ->and(DB::table('members')->where('house', 'senado')->count())->toBe(6)
        ->and(DB::table('members')->where('house', 'camara')->count())->toBe(0);
});

test('finds no contract in a directory without houses', function () {
    $dir = sys_get_temp_dir().'/mandato-empty-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($dir);

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe("no contract found in {$dir}");
});

test('rejects a missing directory', function () {
    $result = runImport(['dir' => '/nonexistent']);

    expect($result['code'])->toBe(2)
        ->and(trim($result['err']))->toBe('contract directory not found: /nonexistent');
});

test('rolls back a house on a failed write', function () {
    importFixtures();
    $before = tableDump();

    $dir = fixtureCopy('camara');
    editJson("{$dir}/members.json", function ($m) {
        $m[0]['name'] = 'Ana Souza Lima';

        return $m;
    });
    editJson("{$dir}/roll-calls/100-1.json", function ($r) {
        $r['votes'][0]['official'] = 'Não';
        $r['votes'][0]['position'] = 'no';

        return $r;
    });

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

    expect(Importer::LOCK_KEY)->toBe(57210057)
        ->and($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe('another import is running')
        ->and(array_sum(tableCounts()))->toBe(0);
});

test('persists no candidacy field and no cpf', function () {
    importFixtures();

    foreach (MANDATE_TABLES as $table) {
        foreach (Schema::getColumnListing($table) as $column) {
            if ("{$table}.{$column}" === 'roll_calls.ballot') {
                continue; // the roll call's ballot type (door 3), not a candidacy's ballot number
            }
            expect($column)->not->toMatch('/cpf|candidacy|office|ballot|situation/i');
        }
    }
    $dump = json_encode(tableDump(), JSON_UNESCAPED_UNICODE);
    foreach (['DEPUTADO FEDERAL', 'SENADOR', '1313', 'APTO', 'consulta_cand_2026_BRASIL.csv'] as $value) {
        expect($dump)->not->toContain($value);
    }
});

test('validates against the v3 schema directory', function () {
    expect(config('mandato.schema_dir'))->toBe(base_path('../etl/schema'));

    $schemas = sys_get_temp_dir().'/mandato-schema-'.bin2hex(random_bytes(6));
    File::copyDirectory(base_path('../etl/schema'), $schemas);
    editJson("{$schemas}/v3/members.schema.json", function ($s) {
        $s['$defs']['member']['properties']['uf']['pattern'] = '^ZZ$';

        return $s;
    });
    config(['mandato.schema_dir' => $schemas]);
    app()->forgetInstance(JsonFiles::class);

    $result = runImport(['dir' => fixtureDir('camara')]);

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain('members.json')
        ->and($result['err'])->toContain('/0/uf');
});

test("loads the etl's own senate fixture", function () {
    $etl = base_path('../etl/tests/fixtures/v3/senado');
    $files = app(JsonFiles::class);
    $files->validated("{$etl}/meta.json", 'v3/meta.schema.json');
    $files->validated("{$etl}/members.json", 'v3/members.schema.json');
    $files->validated("{$etl}/roll-calls.json", 'v3/roll-calls.schema.json');
    $files->validated("{$etl}/propositions.json", 'v3/propositions.schema.json');
    $files->validated("{$etl}/classification-rules.json", 'v3/classification-rules.schema.json');
    foreach (glob("{$etl}/roll-calls/*.json") as $path) {
        $files->validated($path, 'v3/roll-call.schema.json');
    }

    $dir = sys_get_temp_dir().'/mandato-etl-senado-'.bin2hex(random_bytes(6));
    File::copyDirectory($etl, $dir);
    editJson("{$dir}/members.json", function ($members) {
        foreach ([9103, 9104, 9105] as $id) {
            $member = $members[0];
            $member['id'] = $id;
            $member['name'] = "Senador {$id}";
            $members[] = $member;
        }

        return $members;
    });

    $result = runImport(['dir' => $dir]);

    expect($result['code'])->toBe(0, $result['err']);
    $officials = DB::table('votes')->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
        ->where('roll_calls.source_id', '6923')->orderBy('votes.id')->pluck('votes.official')->all();
    expect($officials)->toHaveCount(5)
        ->and(collect($officials)->sort()->values()->all())->toBe(collect(['Sim', 'Não', 'P-NRV', 'Presidente (art. 51 RISF)', 'Licença'])->sort()->values()->all());
});

test('resolves only the v3 reader', function () {
    expect(ContractReaders::SUPPORTED_SCHEMA_VERSIONS)->toBe([3])
        ->and(ContractReaders::for(3))->toBeInstanceOf(V3Reader::class)
        ->and(ContractReaders::for(2))->toBeNull()
        ->and(ContractReaders::for(4))->toBeNull()
        ->and(class_exists('App\\Contract\\V2Reader'))->toBeFalse()
        ->and(file_exists(app_path('Contract/V2Reader.php')))->toBeFalse();
});

test('defaults the contract directory to data v3', function () {
    expect(config('mandato.contract_dir'))->toBe(base_path('../data/v3'));

    config(['mandato.contract_dir' => fixtureDir()]);
    $result = runImport([]);

    expect($result['code'])->toBe(0)
        ->and(DB::table('contract_imports')->orderBy('id')->pluck('house')->all())->toBe(['camara', 'senado']);
});

test('imports from the configured directory when none is given', function () {
    config(['mandato.contract_dir' => fixtureDir()]);

    $result = runImport([]);

    expect($result['code'])->toBe(0)
        ->and(explode("\n", trim($result['out'])))->toBe(['Imported '.CAMARA_LINE, 'Imported '.SENADO_LINE])
        ->and(DB::table('members')->count())->toBe(9);

    config(['mandato.contract_dir' => '/nonexistent-contract-dir']);
    $missing = runImport([]);

    expect($missing['code'])->toBe(2)
        ->and($missing['err'])->toContain('contract directory not found: /nonexistent-contract-dir');
});

test('dry run checks each house and writes nothing', function () {
    $result = runImport(['dir' => fixtureDir(), '--dry-run' => true]);

    expect($result['code'])->toBe(0)
        ->and(explode("\n", trim($result['out'])))->toBe(['Would import '.CAMARA_LINE, 'Would import '.SENADO_LINE])
        ->and(array_sum(tableCounts()))->toBe(0);

    $dir = fixtureCopy('camara');
    editJson("{$dir}/meta.json", fn ($m) => [...$m, 'schema_version' => 2]);
    $refused = runImport(['dir' => $dir, '--dry-run' => true]);

    expect($refused['code'])->toBe(1)
        ->and(array_sum(tableCounts()))->toBe(0);
});
