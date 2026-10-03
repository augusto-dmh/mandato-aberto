<?php

use App\Console\Commands\FetchPhotos;
use App\Import\Importer;
use App\Media\Photos;
use App\Models\House;
use App\Models\PhotoVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\Support\Jpeg;

// share-cards S1: the official photo is cached from the house, byte for byte (C1-C14, C80).

beforeEach(function () {
    Storage::fake('media');
    Sleep::fake();
});

/** The first-hop photo URLs requested since `$from` (redirect targets left out). */
function firstHops(int $from = 0): array
{
    return array_values(array_filter(array_slice(requestedUrls(), $from), fn (string $u) => ! str_contains($u, 'legis.senado')));
}

const ALL_PHOTO_URLS = [
    'https://www.camara.leg.br/internet/deputado/bandep/101.jpg',
    'https://www.camara.leg.br/internet/deputado/bandep/102.jpg',
    'https://www.camara.leg.br/internet/deputado/bandep/103.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9101.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9102.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9103.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9104.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9105.jpg',
    'https://www.senado.leg.br/senadores/img/fotos-oficiais/senador9106.jpg',
];

function setChecked(string $house, string $id, string $at): void
{
    PhotoVersion::query()->where('house', $house)->where('member_source_id', $id)->update(['checked_at' => $at]);
}

test('photos requests only members without a current photo or with a stale check', function () {
    importFixtures();
    $answers = [camaraPhotoUrl('102') => Http::response('gone', 404)];
    fakePhotoHosts($answers);

    expect(runCommand('mandato:photos')['code'])->toBe(0);
    $first = firstHops();
    expect($first)->toHaveCount(9);
    expect(array_values(array_unique($first)))->toEqualCanonicalizing(ALL_PHOTO_URLS);

    // 102 failed, so it has no current photo and is requested again; everyone else is fresh.
    $mark = count(requestedUrls());
    runCommand('mandato:photos');
    expect(firstHops($mark))->toBe([camaraPhotoUrl('102')]);

    unset($answers[camaraPhotoUrl('102')]);
    $mark = count(requestedUrls());
    runCommand('mandato:photos');
    expect(firstHops($mark))->toBe([camaraPhotoUrl('102')]);

    $mark = count(requestedUrls());
    runCommand('mandato:photos');
    expect(firstHops($mark))->toBe([]);

    setChecked('camara', '101', now()->subDays(8)->toDateTimeString());
    $mark = count(requestedUrls());
    runCommand('mandato:photos');
    expect(firstHops($mark))->toBe([camaraPhotoUrl('101')]);

    setChecked('camara', '101', now()->subDays(6)->toDateTimeString());
    $mark = count(requestedUrls());
    runCommand('mandato:photos');
    expect(firstHops($mark))->toBe([]);
});

test('photos narrows by house and member', function () {
    importFixtures();
    fakePhotoHosts();

    runCommand('mandato:photos', ['--house' => 'senado']);
    expect(firstHops())->toEqualCanonicalizing(array_slice(ALL_PHOTO_URLS, 3));

    $mark = count(requestedUrls());
    runCommand('mandato:photos', ['--house' => 'camara', '--member' => '102']);
    expect(firstHops($mark))->toBe([camaraPhotoUrl('102')]);

    $mark = count(requestedUrls());
    runCommand('mandato:photos', ['--house' => 'senado', '--stale-after' => '0']);
    expect(firstHops($mark))->toEqualCanonicalizing(array_slice(ALL_PHOTO_URLS, 3));
});

test('photos stores the official bytes unaltered with their version', function () {
    importFixtures();
    fakePhotoHosts();
    runCommand('mandato:photos');

    $deputy = Jpeg::make(354, 472, 101);
    $row = PhotoVersion::query()->where('house', 'camara')->where('member_source_id', '101')->sole();
    expect($row->sha256)->toBe(hash('sha256', $deputy))
        ->and(hash('sha256', (string) Storage::disk('media')->get("photos/{$row->sha256}.jpg")))->toBe(hash('sha256', $deputy))
        ->and($row->source_url)->toBe(camaraPhotoUrl('101'))
        ->and($row->final_url)->toBe(camaraPhotoUrl('101'))
        ->and($row->bytes)->toBe(strlen($deputy))
        ->and([$row->width, $row->height])->toBe([354, 472])
        ->and($row->fetched_at)->not->toBeNull()
        ->and($row->checked_at)->not->toBeNull();

    $senator = Jpeg::make(480, 600, 9101);
    $row = PhotoVersion::query()->where('house', 'senado')->where('member_source_id', '9101')->sole();
    expect($row->sha256)->toBe(hash('sha256', $senator))
        ->and(hash('sha256', (string) Storage::disk('media')->get("photos/{$row->sha256}.jpg")))->toBe(hash('sha256', $senator))
        ->and($row->source_url)->toBe(senadoPhotoUrl('9101'))
        ->and($row->final_url)->toBe('https://legis.senado.leg.br/senadores/img/fotos-oficiais/senador9101.jpg')
        ->and($row->bytes)->toBe(strlen($senator))
        ->and([$row->width, $row->height])->toBe([480, 600]);
});

test('photos writes each file once', function () {
    importFixtures();
    $same = Jpeg::make(354, 472, 777);
    $answers = [camaraPhotoUrl('101') => Http::response($same), camaraPhotoUrl('102') => Http::response($same)];
    fakePhotoHosts($answers);
    runCommand('mandato:photos', ['--house' => 'camara']);

    $sha = hash('sha256', $same);
    expect(PhotoVersion::query()->where('sha256', $sha)->pluck('member_source_id')->sort()->values()->all())->toBe(['101', '102'])
        ->and(Storage::disk('media')->files('photos'))->toContain("photos/{$sha}.jpg")
        ->and(count(Storage::disk('media')->files('photos')))->toBe(2);

    $file = Storage::disk('media')->path("photos/{$sha}.jpg");
    touch($file, 1_000_000_000);
    clearstatcache();
    runCommand('mandato:photos', ['--house' => 'camara', '--stale-after' => '0']);
    clearstatcache();
    expect(filemtime($file))->toBe(1_000_000_000);
});

test('photos records no new version for unchanged bytes', function () {
    importFixtures();
    fakePhotoHosts();
    runCommand('mandato:photos', ['--house' => 'camara']);
    setChecked('camara', '101', '2026-01-01 00:00:00');
    $before = PhotoVersion::query()->where('member_source_id', '101')->sole();

    $result = runCommand('mandato:photos', ['--house' => 'camara']);

    $after = PhotoVersion::query()->where('member_source_id', '101')->sole();
    expect($after->id)->toBe($before->id)
        ->and($after->fetched_at->equalTo($before->fetched_at))->toBeTrue()
        ->and($after->checked_at->greaterThan($before->checked_at))->toBeTrue()
        ->and(trim($result['out']))->toBe('photos camara: 1 checked, 0 new, 1 unchanged, 0 failed');
});

test('photos keeps the current photo when a fetch fails', function (mixed $answer, string $reason) {
    importFixtures();
    $answers = [];
    fakePhotoHosts($answers);
    runCommand('mandato:photos', ['--house' => 'camara', '--member' => '101']);
    $current = Photos::currentOf(House::Camara, '101');
    expect($current)->not->toBeNull();
    $files = Storage::disk('media')->allFiles();

    $url = camaraPhotoUrl('101');
    $made = $answer($url);
    $answers = is_array($made) ? $made : [$url => $made];
    $result = runCommand('mandato:photos', ['--house' => 'camara', '--member' => '101', '--stale-after' => '0']);

    expect(trim($result['err']))->toBe("photo failed camara 101: {$reason}")
        ->and(Storage::disk('media')->allFiles())->toBe($files)
        ->and(PhotoVersion::query()->count())->toBe(1)
        ->and(Photos::currentOf(House::Camara, '101')?->sha256)->toBe($current->sha256);
})->with([
    'no answer' => [fn () => Http::failedConnection('cURL error 28: Operation timed out after 15000 milliseconds'), 'no response'],
    'status 404' => [fn () => Http::response('', 404), 'status 404'],
    'four redirects' => [fn (string $url) => [
        $url => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/a.jpg']),
        'https://www.camara.leg.br/a.jpg' => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/b.jpg']),
        'https://www.camara.leg.br/b.jpg' => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/c.jpg']),
        'https://www.camara.leg.br/c.jpg' => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/d.jpg']),
        'https://www.camara.leg.br/d.jpg' => Http::response(Jpeg::make(354, 472, 5)),
    ], 'too many redirects'],
    'redirect to another host' => [fn () => Http::response('', 301, ['Location' => 'https://example.org/f.jpg']), 'redirect to example.org'],
    'redirect over http' => [fn () => Http::response('', 301, ['Location' => 'http://www.camara.leg.br/f.jpg']), 'redirect over http'],
    'html body' => [fn () => Http::response('<html><body>Página</body></html>', 200, ['Content-Type' => 'text/html']), 'not a jpeg'],
    'png body' => [fn () => Http::response(Jpeg::png(354, 472)), 'not a jpeg'],
    'jpeg signature only' => [fn () => Http::response("\xFF\xD8\xFF".str_repeat("\0", 2048)), 'not a jpeg'],
    'three mebibytes' => [fn () => Http::response("\xFF\xD8\xFF".str_repeat('a', 3 * 1024 * 1024)), 'larger than 2 MiB'],
    'eighty pixels' => [fn () => Http::response(Jpeg::make(80, 80, 3)), 'smaller than 100 x 100'],
]);

test('photos refuses a photo under 100 pixels on either side', function (int $width, int $height, ?string $reason) {
    importFixtures();
    $body = Jpeg::make($width, $height, 7);
    $answers = [camaraPhotoUrl('101') => Http::response($body)];
    fakePhotoHosts($answers);

    $result = runCommand('mandato:photos', ['--house' => 'camara', '--member' => '101']);

    if ($reason === null) {
        expect($result['err'])->toBe('')
            ->and(Photos::currentOf(House::Camara, '101')?->sha256)->toBe(hash('sha256', $body));
    } else {
        expect(trim($result['err']))->toBe("photo failed camara 101: {$reason}")
            ->and(PhotoVersion::query()->count())->toBe(0)
            ->and(Storage::disk('media')->allFiles())->toBe([]);
    }
})->with([
    'wide enough, too short' => [200, 80, 'smaller than 100 x 100'],
    'tall enough, too narrow' => [80, 200, 'smaller than 100 x 100'],
    'one hundred square' => [100, 100, null],
]);

test('photos follows up to three redirects', function () {
    importFixtures();
    $url = camaraPhotoUrl('101');
    $answers = [
        $url => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/a.jpg']),
        'https://www.camara.leg.br/a.jpg' => Http::response('', 302, ['Location' => '/b.jpg']),
        'https://www.camara.leg.br/b.jpg' => Http::response('', 302, ['Location' => 'https://www.camara.leg.br/c.jpg']),
        'https://www.camara.leg.br/c.jpg' => Http::response(Jpeg::make(354, 472, 6)),
    ];
    fakePhotoHosts($answers);

    $result = runCommand('mandato:photos', ['--house' => 'camara', '--member' => '101']);

    expect($result['err'])->toBe('')
        ->and(PhotoVersion::query()->sole()->final_url)->toBe('https://www.camara.leg.br/c.jpg');
});

test('photos identifies itself and checks every hop', function () {
    importFixtures();
    $seen = [];
    $answers = [];
    foreach (ALL_PHOTO_URLS as $url) {
        $answers[$url] = function ($request, array $options) use (&$seen, $url) {
            $seen[] = [$request->header('User-Agent')[0] ?? null, $options['timeout'] ?? null, $options['allow_redirects'] ?? null];

            return str_contains($url, 'camara')
                ? Http::response(Jpeg::make(354, 472, 1))
                : Http::response('', 301, ['Location' => str_replace('www.', 'legis.', $url)]);
        };
    }
    fakePhotoHosts($answers);
    runCommand('mandato:photos');

    expect($seen)->toHaveCount(9);
    foreach ($seen as $s) {
        expect($s)->toBe(['mandato-aberto-app (+https://github.com/augusto-dmh/mandato-aberto)', 15, false]);
    }

    DB::table('members')->where('source_id', '103')->update(['photo_url' => 'https://example.org/103.jpg']);
    $mark = count(requestedUrls());
    $result = runCommand('mandato:photos', ['--house' => 'camara', '--member' => '103']);
    expect(trim($result['err']))->toBe('photo failed camara 103: host not allowed')
        ->and(array_slice(requestedUrls(), $mark))->toBe([]);
});

test('photos fetches four at a time with a pause', function () {
    importFixtures();
    fakePhotoHosts();
    $atPause = [];
    Sleep::whenFakingSleep(function ($duration) use (&$atPause) {
        $atPause[] = [count(firstHops()), $duration->totalMilliseconds];
    });

    $result = runCommand('mandato:photos');

    // 9 members: Câmara's 3 in one batch, the Senate's 6 in batches of 4 and 2, a pause before each batch but the first
    expect($result['code'])->toBe(0)
        ->and($atPause)->toBe([[3, 250.0], [7, 250.0]])
        ->and(count(firstHops()))->toBe(9);
});

test('photos prints one summary per house', function () {
    importFixtures();
    $answers = [camaraPhotoUrl('102') => Http::response('', 404)];
    fakePhotoHosts($answers);

    $result = runCommand('mandato:photos');

    expect($result['code'])->toBe(0)
        ->and(explode("\n", trim($result['out'])))->toBe([
            'photos camara: 3 checked, 2 new, 0 unchanged, 1 failed',
            'photos senado: 6 checked, 6 new, 0 unchanged, 0 failed',
        ])
        ->and(trim($result['err']))->toBe('photo failed camara 102: status 404');
});

test('photos exits 1 when every fetch of a house failed', function () {
    importFixtures();
    $answers = [];
    foreach (['101', '102', '103'] as $id) {
        $answers[camaraPhotoUrl($id)] = Http::response('', 503);
    }
    fakePhotoHosts($answers);

    $result = runCommand('mandato:photos');
    expect($result['code'])->toBe(1)
        ->and($result['out'])->toContain('photos senado: 6 checked, 6 new, 0 unchanged, 0 failed');

    $result = runCommand('mandato:photos', ['--house' => 'senado', '--member' => '9999']);
    expect($result['code'])->toBe(0)
        ->and(trim($result['out']))->toBe('photos senado: 0 checked, 0 new, 0 unchanged, 0 failed');
});

test('photos exits 1 when storage fails', function () {
    importFixtures();
    fakePhotoHosts();
    $root = Storage::disk('media')->path('');
    chmod($root, 0555);

    try {
        $result = runCommand('mandato:photos', ['--house' => 'camara', '--member' => '101']);
    } finally {
        chmod($root, 0755);
    }

    expect($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe('photo failed camara 101: storage write failed')
        ->and(PhotoVersion::query()->count())->toBe(0);
});

test('photos refuses while another run holds the lock', function () {
    importFixtures();
    fakePhotoHosts();
    config(['database.connections.second' => config('database.connections.pgsql')]);
    $second = DB::connection('second');
    $second->select('select pg_advisory_lock(?)', [FetchPhotos::LOCK_KEY]);

    try {
        $result = runCommand('mandato:photos');
    } finally {
        $second->select('select pg_advisory_unlock(?)', [FetchPhotos::LOCK_KEY]);
        $second->disconnect();
    }

    expect(FetchPhotos::LOCK_KEY)->not->toBe(Importer::LOCK_KEY)
        ->and($result['code'])->toBe(1)
        ->and(trim($result['err']))->toBe('another photo run is running')
        ->and(requestedUrls())->toBe([]);
});

test('photos rejects bad options with the usage', function (array $options) {
    importFixtures();
    fakePhotoHosts();

    $result = runCommand('mandato:photos', $options);

    expect($result['code'])->toBe(2)
        ->and(trim($result['err']))->toBe('usage: mandato:photos [--house=camara|senado] [--member=<id>] [--stale-after=<days>]')
        ->and(requestedUrls())->toBe([]);
})->with([
    'unknown house' => [['--house' => 'presidencia']],
    'member without house' => [['--member' => '101']],
    'negative stale-after' => [['--stale-after' => '-1']],
    'word stale-after' => [['--stale-after' => 'x']],
]);

test('a photo shared by two members is no one\'s photo', function () {
    importFixtures();
    $placeholder = Jpeg::make(480, 600, 4242);
    $answers = [
        senadoPhotoUrl('9104', final: true) => Http::response($placeholder),
        senadoPhotoUrl('9105', final: true) => Http::response($placeholder),
    ];
    fakePhotoHosts($answers);
    runCommand('mandato:photos', ['--house' => 'senado']);

    expect(Photos::currentOf(House::Senado, '9104'))->toBeNull()
        ->and(Photos::currentOf(House::Senado, '9105'))->toBeNull()
        ->and(Photos::currentOf(House::Senado, '9106'))->not->toBeNull();
    requireSsr();
    foreach (['9104', '9105'] as $id) {
        $doc = html($this->get("/senadores/{$id}/"));
        expect($doc->querySelector('.ma-hero .ma-photo__initials'))->not->toBeNull()
            ->and($doc->querySelector('.ma-hero img'))->toBeNull();
    }

    $mark = count(requestedUrls());
    runCommand('mandato:photos', ['--house' => 'senado']);
    expect(firstHops($mark))->toEqualCanonicalizing([senadoPhotoUrl('9104'), senadoPhotoUrl('9105')]);

    $answers[senadoPhotoUrl('9105', final: true)] = Http::response(Jpeg::make(480, 600, 9105));
    runCommand('mandato:photos', ['--house' => 'senado']);
    expect(Photos::currentOf(House::Senado, '9104')?->sha256)->toBe(hash('sha256', $placeholder))
        ->and(Photos::currentOf(House::Senado, '9105')?->sha256)->toBe(hash('sha256', Jpeg::make(480, 600, 9105)));
});

test('a photo shared by two members leaves both cards without a photo', function () {
    importFixtures();
    $placeholder = Jpeg::make(480, 600, 4242);
    $answers = [
        senadoPhotoUrl('9104', final: true) => Http::response($placeholder),
        senadoPhotoUrl('9105', final: true) => Http::response($placeholder),
    ];
    fakePhotoHosts($answers);
    $payload = function (string $id) {
        $member = DB::table('members')->where('house', 'senado')->where('source_id', $id)->value('id');

        return memberPayload(House::Senado, $id, (int) DB::table('memberships')->where('member_id', $member)->max('legislature_number'));
    };
    runCommand('mandato:photos', ['--house' => 'senado']);

    expect(PhotoVersion::query()->whereIn('member_source_id', ['9104', '9105'])->pluck('sha256')->unique()->all())->toBe([hash('sha256', $placeholder)])
        ->and($payload('9104')['photoSha256'])->toBeNull()
        ->and($payload('9105')['photoSha256'])->toBeNull()
        ->and($payload('9106')['photoSha256'])->toBe(Photos::currentOf(House::Senado, '9106')->sha256);

    $answers[senadoPhotoUrl('9105', final: true)] = Http::response(Jpeg::make(480, 600, 9105));
    runCommand('mandato:photos', ['--house' => 'senado']);
    expect($payload('9104')['photoSha256'])->toBe(hash('sha256', $placeholder))
        ->and($payload('9105')['photoSha256'])->toBe(hash('sha256', Jpeg::make(480, 600, 9105)));
});
