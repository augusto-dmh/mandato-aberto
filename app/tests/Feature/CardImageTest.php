<?php

use App\Cards\Code;
use App\Models\House;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Symfony\Component\Process\Process as SymfonyProcess;
use Tests\Support\Jpeg;

// share-cards S3: card images from the design template in three sizes (C20, C22-C31).

beforeEach(function () {
    Storage::fake('media');
    Sleep::fake();
});

const CARD_FORMATS = ['1200x630' => [1200, 630], '1080x1350' => [1080, 1350], '1080x1920' => [1080, 1920]];

/** The 4 card subjects of the fixtures, by route: the subject path and how to compute its payload. */
function cardSubjects(): array
{
    return [
        'deputy' => ['/deputados/101/legislatura/58/', fn () => memberPayload(House::Camara, '101', 58)],
        'senator' => ['/senadores/9101/legislatura/57/', fn () => memberPayload(House::Senado, '9101', 57)],
        'camara roll call' => ['/votacoes/100-1/', fn () => rollCallPayload(House::Camara, '100-1')],
        'senate roll call' => ['/senado/votacoes/7001/', fn () => rollCallPayload(House::Senado, '7001')],
    ];
}

/** @return array{0: int, 1: int} width and height from a PNG's IHDR */
function pngSize(string $png): array
{
    expect(substr($png, 0, 8))->toBe("\x89PNG\r\n\x1a\n");

    return [unpack('N', substr($png, 16, 4))[1], unpack('N', substr($png, 20, 4))[1]];
}

/** A 1 x 1 PNG standing in for the renderer's output when the renderer is faked. */
function stubPng(): string
{
    return Jpeg::png(1, 1);
}

test('card routes serve a png of each format', function () {
    requireCardRenderer();
    importFixtures();

    foreach (cardSubjects() as $name => [$path, $payload]) {
        $code = Code::of($payload());
        foreach (CARD_FORMATS as $format => $size) {
            $response = $this->get("{$path}card/{$code}/{$format}.png");
            $response->assertStatus(200);
            expect($response->headers->get('Content-Type'))->toBe('image/png', "{$name} {$format}")
                ->and(pngSize((string) $response->getContent()))->toBe($size, "{$name} {$format}")
                ->and(cacheDirectives($response))->toBe(['immutable', 'max-age=31536000', 'public'])
                ->and($response->headers->has('Set-Cookie'))->toBeFalse();
        }
    }
});

test('the snapshot is stored before the first render', function () {
    importFixtures();
    $code = Code::of(memberPayload(House::Camara, '101', 58));
    $rowsWhenRendering = null;
    Process::fake(function (PendingProcess $process) use (&$rowsWhenRendering) {
        $rowsWhenRendering = DB::table('card_snapshots')->count();

        return Process::result(output: stubPng());
    });
    expect(DB::table('card_snapshots')->count())->toBe(0);

    $response = $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png");

    $response->assertStatus(200);
    expect($rowsWhenRendering)->toBe(1);
    $row = DB::table('card_snapshots')->sole();
    expect([$row->code, $row->kind, $row->house, $row->source_id, $row->legislature, $row->template])->toBe([$code, 'member', 'camara', '101', 58, 1])
        ->and(json_decode($row->payload, true))->toEqual([
            'template' => 1, 'kind' => 'member', 'house' => 'camara', 'sourceId' => '101', 'legislature' => 58,
            'generatedAt' => '2027-03-02T02:30:00Z', 'photoSha256' => null, 'name' => 'Ana Souza', 'party' => 'PT', 'uf' => 'SP',
            'figures' => [
                ['label' => 'Participação em votações nominais do plenário', 'count' => 1, 'total' => 1],
                ['label' => 'Votos iguais à orientação do governo', 'count' => 0, 'total' => 0],
                ['label' => 'Votos iguais à maioria do próprio partido', 'count' => 1, 'total' => 1],
            ],
            'votes' => [['rollCallId' => '300-1', 'date' => '2027-02-15', 'position' => 'yes']],
        ])
        ->and(Storage::disk('media')->get("cards/{$code}/1200x630.png"))->toBe($response->getContent());
});

test('a stored card is served without rendering again', function () {
    importFixtures();
    $code = Code::of(memberPayload(House::Camara, '101', 58));
    $png = Jpeg::png(3, 2);
    Process::fake(['*' => Process::result(output: $png)]);

    $first = $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png");
    $again = $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png");
    $other = $this->get("/deputados/101/legislatura/58/card/{$code}/1080x1350.png");

    expect([$first->status(), $again->status(), $other->status()])->toBe([200, 200, 200])
        ->and($again->getContent())->toBe($first->getContent());
    Process::assertRanTimes(fn () => true, 2);
});

test('an old stored code renders from its snapshot', function () {
    importFixtures();
    $old = Code::of(memberPayload(House::Camara, '101', 58));
    $inputs = [];
    Process::fake(function (PendingProcess $process) use (&$inputs) {
        $inputs[] = json_decode((string) $process->input, true);

        return Process::result(output: stubPng());
    });
    $this->get("/deputados/101/legislatura/58/card/{$old}/1200x630.png")->assertStatus(200);

    $member = DB::table('members')->where('house', 'camara')->where('source_id', '101')->value('id');
    $rollCall = DB::table('roll_calls')->where('house', 'camara')->where('source_id', '300-1')->value('id');
    DB::table('votes')->where('member_id', $member)->where('roll_call_id', $rollCall)->update(['position' => 'no', 'official' => 'Não']);
    $current = Code::of(memberPayload(House::Camara, '101', 58));
    expect($current)->not->toBe($old);

    $this->get("/deputados/101/legislatura/58/card/{$old}/1080x1350.png")->assertStatus(200);

    expect(end($inputs)['props']['votes'])->toEqual([['rollCallId' => '300-1', 'date' => '2027-02-15', 'position' => 'yes']])
        ->and(end($inputs)['props']['code'])->toBe($old)
        ->and(end($inputs)['format'])->toBe('feed');
});

test('an unknown well-formed code redirects to the current card', function () {
    importFixtures();
    Process::fake(['*' => Process::result(output: stubPng())]);
    $other = Code::of(memberPayload(House::Camara, '102', 57));
    $this->get("/deputados/102/legislatura/57/card/{$other}/1200x630.png")->assertStatus(200);

    foreach (cardSubjects() as $name => [$path, $payload]) {
        $current = Code::of($payload());
        foreach (['20270301-00000000', $other] as $code) {
            $this->get("{$path}card/{$code}/1080x1350.png")
                ->assertStatus(302)
                ->assertHeader('Location', config('app.url')."{$path}card/{$current}/1080x1350.png");
        }
    }
});

test('card urls that name nothing answer 404', function (string $url) {
    importFixtures();
    Process::fake(['*' => Process::result(output: stubPng())]);
    $code = Code::of(memberPayload(House::Camara, '101', 58));

    $this->get(str_replace('{code}', $code, $url))->assertStatus(404);
    Process::assertNothingRan();
})->with([
    'deputy 999' => ['/deputados/999/legislatura/58/card/{code}/1200x630.png'],
    'senator with a deputy id' => ['/senadores/101/legislatura/58/card/{code}/1200x630.png'],
    'legislature not held' => ['/deputados/101/legislatura/59/card/{code}/1200x630.png'],
    'roll call 999-9' => ['/votacoes/999-9/card/{code}/1200x630.png'],
    'senate roll call 1' => ['/senado/votacoes/1/card/{code}/1200x630.png'],
    'format 800x600' => ['/deputados/101/legislatura/58/card/{code}/800x600.png'],
    'format jpg' => ['/deputados/101/legislatura/58/card/{code}/1200x630.jpg'],
    'lowercase code' => ['/deputados/101/legislatura/58/card/20270301-k7q29xpd/1200x630.png'],
    'code with I' => ['/deputados/101/legislatura/58/card/20270301-K7Q29XPI/1200x630.png'],
    'seven digits' => ['/deputados/101/legislatura/58/card/2027031-K7Q29XPD/1200x630.png'],
]);

test('a failed render answers 503', function () {
    importFixtures();
    Log::spy();
    Process::fake(['*' => Process::result(output: '', errorOutput: 'boom', exitCode: 1)]);

    foreach (cardSubjects() as $name => [$path, $payload]) {
        $code = Code::of($payload());
        $response = $this->get("{$path}card/{$code}/1200x630.png");
        $response->assertStatus(503)->assertHeader('Retry-After', '60');
        expect(Storage::disk('media')->exists("cards/{$code}/1200x630.png"))->toBeFalse($name);
        Log::shouldHaveReceived('error')->with("card render failed {$code} 1200x630: boom")->once();
    }
});

test('a render that runs too long answers 503', function () {
    importFixtures();
    expect(config('mandato.card_render_timeout'))->toBe(15);
    config(['mandato.card_renderer' => ['sh', '-c', 'sleep 5'], 'mandato.card_render_timeout' => 1]);
    Log::spy();
    $code = Code::of(memberPayload(House::Camara, '101', 58));

    $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png")->assertStatus(503)->assertHeader('Retry-After', '60');

    expect(Storage::disk('media')->exists("cards/{$code}/1200x630.png"))->toBeFalse();
    Log::shouldHaveReceived('error')->with("card render failed {$code} 1200x630: timed out")->once();
});

test('a third simultaneous render answers 503', function () {
    importFixtures();
    Process::fake(['*' => Process::result(output: stubPng())]);
    $code = Code::of(memberPayload(House::Camara, '101', 58));
    $slots = [Cache::lock('card-render-slot:0', 60), Cache::lock('card-render-slot:1', 60)];
    expect($slots[0]->get())->toBeTrue()->and($slots[1]->get())->toBeTrue();

    $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png")->assertStatus(503)->assertHeader('Retry-After', '60');
    Process::assertNothingRan();

    Storage::disk('media')->put("cards/{$code}/1080x1350.png", stubPng());
    $this->get("/deputados/101/legislatura/58/card/{$code}/1080x1350.png")->assertStatus(200);

    foreach ($slots as $slot) {
        $slot->release();
    }
});

test('one card is rendered once under simultaneous requests', function () {
    importFixtures();
    config(['mandato.lock_store' => 'file']);
    Process::fake(['*' => Process::result(output: stubPng())]);
    $code = Code::of(memberPayload(House::Camara, '101', 58));
    $file = Storage::disk('media')->path("cards/{$code}/1200x630.png");
    $ready = sys_get_temp_dir().'/card-lock-'.bin2hex(random_bytes(4));
    $png = Jpeg::png(5, 5);

    // Another request's render in flight: it holds the card's lock, writes the PNG after a second, then releases.
    $holder = new SymfonyProcess(['php', base_path('tests/Support/hold-card-lock.php'), "card:{$code}:1200x630", $file, $ready], base_path());
    $holder->setInput($png);
    $holder->start();
    for ($i = 0; $i < 100 && ! is_file($ready); $i++) {
        usleep(50_000);
    }
    expect(is_file($ready))->toBeTrue($holder->getErrorOutput());

    $response = $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png");
    $holder->wait();

    $response->assertStatus(200);
    expect($response->getContent())->toBe($png);
    Process::assertNothingRan();
    @unlink($ready);
});

test('a suppressed member renders the initials on page and card', function () {
    requireSsr();
    storeFixturePhotos();
    $inputs = [];
    Process::fake(function (PendingProcess $process) use (&$inputs) {
        $inputs[] = json_decode((string) $process->input, true);

        return Process::result(output: stubPng());
    });
    $before = Code::of(memberPayload(House::Camara, '101', 58));
    $this->get("/deputados/101/legislatura/58/card/{$before}/1200x630.png")->assertStatus(200);
    expect($inputs[0]['photo'])->toStartWith('data:image/jpeg;base64,');

    config(['mandato.photo_suppressed' => ['camara:101']]);
    $after = Code::of(memberPayload(House::Camara, '101', 58));
    expect($after)->not->toBe($before);

    $doc = html($this->get('/deputados/101/'));
    expect($doc->querySelector('.ma-hero .ma-photo__initials'))->not->toBeNull()
        ->and($doc->querySelector('.ma-hero img'))->toBeNull();

    // a card rendered after the suppression, even from a snapshot taken before it, draws the initials
    $this->get("/deputados/101/legislatura/58/card/{$after}/1200x630.png")->assertStatus(200);
    $this->get("/deputados/101/legislatura/58/card/{$before}/1080x1350.png")->assertStatus(200);
    expect($inputs[1]['photo'])->toBeNull()
        ->and($inputs[2]['photo'])->toBeNull();
});
