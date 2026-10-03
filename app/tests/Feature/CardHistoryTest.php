<?php

use App\Cards\Code;
use App\Models\House;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Jpeg;

// share-cards S7: storage stays bounded and history stays whole (C66-C69).

beforeEach(function () {
    Storage::fake('media');
});

/** Sets Ana Souza's vote on `300-1`, her only vote in the 58th legislature. */
function setAnasVote(string $position, string $official): void
{
    $member = DB::table('members')->where('house', 'camara')->where('source_id', '101')->value('id');
    $rollCall = DB::table('roll_calls')->where('house', 'camara')->where('source_id', '300-1')->value('id');
    DB::table('votes')->where('member_id', $member)->where('roll_call_id', $rollCall)->update(['position' => $position, 'official' => $official]);
}

/** Requests a card image and fails the test unless it answers 200; returns the stored file's path on the media disk. */
function fetchCard(string $subjectPath, string $code, string $format = '1200x630'): string
{
    test()->get("{$subjectPath}card/{$code}/{$format}.png")->assertStatus(200);
    $path = "cards/{$code}/{$format}.png";
    expect(Storage::disk('media')->exists($path))->toBeTrue($path);

    return $path;
}

function ageFile(string $path, int $days): void
{
    touch(Storage::disk('media')->path($path), time() - $days * 86400);
}

/** Every row of a table, ordered by id. */
function rowsOf(string $table): array
{
    return DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
}

test('prune deletes old images of codes that are not the latest', function () {
    importFixtures();
    $inputs = [];
    $renders = 0;
    // a different PNG per render, so a file's size tells which render wrote it
    Process::fake(function (PendingProcess $process) use (&$inputs, &$renders) {
        $inputs[] = json_decode((string) $process->input, true);
        $renders++;

        return Process::result(output: Jpeg::png($renders, $renders));
    });

    $a = Code::of(memberPayload(House::Camara, '101', 58));
    $fileA = fetchCard('/deputados/101/legislatura/58/', $a);
    setAnasVote('no', 'Não');
    $d = Code::of(memberPayload(House::Camara, '101', 58));
    $fileD = fetchCard('/deputados/101/legislatura/58/', $d);
    setAnasVote('abstention', 'Abstenção');
    $b = Code::of(memberPayload(House::Camara, '101', 58));
    $fileB = fetchCard('/deputados/101/legislatura/58/', $b);
    $c = Code::of(memberPayload(House::Camara, '102', 57));
    $fileC = fetchCard('/deputados/102/legislatura/57/', $c);
    expect(array_unique([$a, $b, $c, $d]))->toHaveCount(4);
    ageFile($fileA, 31);
    ageFile($fileB, 31);
    ageFile($fileC, 31);
    ageFile($fileD, 10);
    $bytesA = Storage::disk('media')->size($fileA);
    $snapshots = rowsOf('card_snapshots');
    expect($snapshots)->toHaveCount(4);

    $result = runCommand('mandato:cards:prune');

    expect($result['code'])->toBe(0, $result['err'])
        ->and(trim($result['out']))->toBe("cards pruned: 1 files, {$bytesA} bytes")
        ->and(Storage::disk('media')->exists($fileA))->toBeFalse()
        ->and(Storage::disk('media')->exists($fileB))->toBeTrue()
        ->and(Storage::disk('media')->exists($fileC))->toBeTrue()
        ->and(Storage::disk('media')->exists($fileD))->toBeTrue();

    $bytesD = Storage::disk('media')->size($fileD);
    $again = runCommand('mandato:cards:prune', ['--days' => 5]);
    expect($again['code'])->toBe(0, $again['err'])
        ->and(trim($again['out']))->toBe("cards pruned: 1 files, {$bytesD} bytes")
        ->and(Storage::disk('media')->exists($fileD))->toBeFalse()
        ->and(Storage::disk('media')->exists($fileB))->toBeTrue()
        ->and(Storage::disk('media')->exists($fileC))->toBeTrue()
        ->and(rowsOf('card_snapshots'))->toBe($snapshots);

    // AC 48: the pruned card renders again from its snapshot
    $before = $renders;
    fetchCard('/deputados/101/legislatura/58/', $a);
    expect($renders)->toBe($before + 1)
        ->and(end($inputs)['props']['code'])->toBe($a)
        ->and(end($inputs)['props']['votes'])->toEqual([['rollCallId' => '300-1', 'date' => '2027-02-15', 'position' => 'yes']]);
});

test('prune exits 1 when a file cannot be deleted', function () {
    importFixtures();
    Process::fake(['*' => Process::result(output: Jpeg::png(2, 2))]);
    $a = Code::of(memberPayload(House::Camara, '101', 58));
    $fileA = fetchCard('/deputados/101/legislatura/58/', $a);
    setAnasVote('no', 'Não');
    fetchCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));
    ageFile($fileA, 31);
    $dir = dirname(Storage::disk('media')->path($fileA));
    chmod($dir, 0555);

    try {
        $result = runCommand('mandato:cards:prune');
    } finally {
        chmod($dir, 0755);
    }

    expect($result['code'])->toBe(1)
        ->and($result['err'])->toContain("card prune failed {$fileA}")
        ->and(Storage::disk('media')->exists($fileA))->toBeTrue();
});

test('history is never deleted', function () {
    storeFixturePhotos();
    Process::fake(['*' => Process::result(output: Jpeg::png(3, 3))]);
    $old = Code::of(memberPayload(House::Camara, '101', 58));
    ageFile(fetchCard('/deputados/101/legislatura/58/', $old), 40);
    setAnasVote('no', 'Não');
    fetchCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));
    fetchCard('/deputados/103/legislatura/57/', Code::of(memberPayload(House::Camara, '103', 57)));
    fetchCard('/votacoes/100-1/', Code::of(rollCallPayload(House::Camara, '100-1')));
    expect(runCommand('mandato:cards:prune')['code'])->toBe(0);

    $snapshots = rowsOf('card_snapshots');
    $photos = rowsOf('photo_versions');
    $files = Storage::disk('media')->allFiles('photos');
    expect($snapshots)->toHaveCount(4)
        ->and($photos)->toHaveCount(9)
        ->and($files)->not->toBeEmpty();

    // A re-import of the Câmara without member 103's mandate: app-contract-v3's sweep removes it.
    $dir = fixtureCopy('camara');
    foreach (glob("{$dir}/roll-calls/*.json") as $path) {
        $rollCall = readJson($path);
        $rollCall['votes'] = array_values(array_filter($rollCall['votes'], fn ($v) => $v['memberId'] !== 103));
        writeJson($path, $rollCall);
    }
    writeJson("{$dir}/members.json", array_map(function ($m) {
        if ($m['id'] === 103) {
            $m['mandates'] = [];
        }

        return $m;
    }, readJson("{$dir}/members.json")));
    $import = runImport(['dir' => $dir]);
    expect($import['code'])->toBe(0, $import['err'])
        ->and(DB::table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')->where('members.source_id', '103')->exists())->toBeFalse();

    expect(rowsOf('card_snapshots'))->toBe($snapshots)
        ->and(rowsOf('photo_versions'))->toBe($photos)
        ->and(Storage::disk('media')->allFiles('photos'))->toBe($files);

    // No source of this feature deletes or updates either table, apart from a photo's `checked_at`.
    $sources = [...File::allFiles(app_path()), base_path('database/migrations/2026_10_04_000000_create_photo_and_card_tables.php')];
    $touching = 0;
    foreach ($sources as $file) {
        $code = (string) file_get_contents((string) $file);
        if (preg_match('/PhotoVersion|photo_versions|CardSnapshot|card_snapshots/', $code) !== 1) {
            continue;
        }
        $touching++;
        // a query on either table that writes: `CardSnapshot::query()->where(...)->delete();`, `DB::table('photo_versions')->update(...)`
        foreach (explode(';', $code) as $statement) {
            if (preg_match('/PhotoVersion|photo_versions|CardSnapshot|card_snapshots/', $statement) === 1) {
                expect(preg_match('/(delete|forceDelete|truncate|update|upsert|updateOrCreate|updateOrInsert|increment|decrement|destroy|deleteQuietly)\s*\(/', $statement))
                    ->toBe(0, "{$file}: {$statement}");
            }
        }
        // a model held in a variable: `$snapshot->delete()`; a disk's `delete($path)` takes a path
        expect(preg_match('/->\s*(delete|forceDelete|deleteQuietly)\s*\(\s*\)/', $code))->toBe(0, (string) $file)
            ->and(preg_match('/\b(delete\s+from|truncate)\b|\bupdate\s+(photo_versions|card_snapshots)\b/i', $code))->toBe(0, (string) $file);
        preg_match_all('/->\s*update\s*\((.*?)\)\s*;/s', $code, $updates);
        foreach ($updates[1] as $arguments) {
            expect($arguments)->toMatch("/^\\s*\\[\\s*'checked_at'\\s*=>\\s*\\\$\\w+\\s*\\]\\s*$/", (string) $file);
        }
    }
    expect($touching)->toBeGreaterThan(3);
});

test('each render is logged with its time and size', function () {
    importFixtures();
    Log::spy();
    $png = Jpeg::png(4, 4);
    Process::fake(['*' => Process::result(output: $png)]);
    $code = Code::of(memberPayload(House::Camara, '101', 58));

    foreach (['1200x630', '1080x1920'] as $format) {
        $path = fetchCard('/deputados/101/legislatura/58/', $code, $format);
        $bytes = Storage::disk('media')->size($path);
        expect($bytes)->toBeGreaterThan(0);
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message) => preg_match("/^card rendered {$code} {$format} \\d+ ms {$bytes} bytes$/", $message) === 1)
            ->once();
    }
});
