<?php

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Ssr\HttpGateway;
use Tests\Support\CapturedOutput;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** The tables an import writes: the ten contract tables plus `contract_imports`. */
const MANDATE_TABLES = [
    'legislatures', 'members', 'memberships', 'exercise_periods', 'propositions', 'roll_calls', 'votes', 'authorships',
    'classification_rules', 'full_texts', 'contract_imports',
];

/** The app-owned v3 fixtures (plan, Assumptions: test data): `camara/` and `senado/` under one parent. */
function fixtureDir(?string $house = null): string
{
    return base_path('tests/fixtures/v3'.($house === null ? '' : "/{$house}"));
}

/** A writable copy of the fixture parent (or one house) in the system temp directory, for tests that change it. */
function fixtureCopy(?string $house = null): string
{
    $dir = sys_get_temp_dir().'/mandato-contract-'.bin2hex(random_bytes(6));
    File::copyDirectory(fixtureDir($house), $dir);

    return $dir;
}

/** Every page the fixtures render: 6 member pages, 9 roll-call pages and the methodology (C50, C66). */
const RENDERED_PAGES = [
    '/deputados/101/', '/deputados/101/legislatura/57/', '/deputados/102/', '/deputados/103/', '/senadores/9101/', '/senadores/9103/',
    '/votacoes/100-1/', '/votacoes/100-2/', '/votacoes/100-3/', '/votacoes/100-4/', '/votacoes/100-5/', '/votacoes/100-6/', '/votacoes/200-1/',
    '/senado/votacoes/6923/', '/senado/votacoes/7001/',
    '/metodologia/',
];

/** Imports both fixture houses from their parent and fails the test on a non-zero exit. */
function importFixtures(): void
{
    $result = runImport(['dir' => fixtureDir()]);
    expect($result['code'])->toBe(0, $result['err']);
}

/** @return mixed decoded as arrays */
function readJson(string $path): mixed
{
    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

function writeJson(string $path, mixed $data): void
{
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Runs `mandato:import` and returns its exit code, standard output and standard error apart.
 *
 * @param  array<string, mixed>  $parameters
 * @return array{code: int, out: string, err: string}
 */
function runImport(array $parameters): array
{
    $output = new CapturedOutput;
    $code = Artisan::call('mandato:import', $parameters, $output);

    return ['code' => $code, 'out' => $output->fetch(), 'err' => $output->errors()];
}

/** @return array<string, int> */
function tableCounts(): array
{
    return collect(MANDATE_TABLES)->mapWithKeys(fn (string $t) => [$t => DB::table($t)->count()])->all();
}

/**
 * Every row of every mandate table, ordered by key, optionally without the timestamps.
 *
 * @return array<string, list<array<string, mixed>>>
 */
function tableDump(bool $withTimestamps = true): array
{
    return collect(MANDATE_TABLES)->mapWithKeys(function (string $table) use ($withTimestamps) {
        $key = $table === 'legislatures' ? 'number' : 'id';
        $rows = DB::table($table)->orderBy($key)->get()->map(function ($row) use ($withTimestamps) {
            $row = (array) $row;
            if (! $withTimestamps) {
                unset($row['created_at'], $row['updated_at']);
            }

            return $row;
        })->all();

        return [$table => $rows];
    })->all();
}

/** Fails, never skips, when the Inertia SSR server is down: the page checks read server-rendered HTML. */
function requireSsr(): void
{
    if (! app(HttpGateway::class)->isHealthy()) {
        test()->fail('The Inertia SSR server is not running at '.config('inertia.ssr.url').': build with `sail npm run build` and start it with `sail artisan inertia:start-ssr`.');
    }
}

function html(TestResponse $response): HTMLDocument
{
    return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
}

/** Text of an element with whitespace collapsed, leaving out footnote markers. */
function textOf(?Element $element): string
{
    if ($element === null) {
        return '';
    }
    $clone = $element->cloneNode(true);
    foreach ($clone->querySelectorAll('.ma-note-ref, sup') as $marker) {
        $marker->remove();
    }

    return trim((string) preg_replace('/\s+/u', ' ', (string) $clone->textContent));
}

/**
 * The head tags of skeleton AC 14 and AC 22, by name.
 *
 * @return array<string, list<string>>
 */
function headTags(HTMLDocument $doc): array
{
    $tags = ['title' => array_map(fn ($t) => (string) $t->textContent, iterator_to_array($doc->querySelectorAll('head title')))];
    foreach (['description', 'twitter:card'] as $name) {
        $tags[$name] = array_map(fn ($m) => (string) $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll("head meta[name=\"{$name}\"]")));
    }
    foreach (['og:type', 'og:site_name', 'og:locale', 'og:title', 'og:description', 'og:url'] as $property) {
        $tags[$property] = array_map(fn ($m) => (string) $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll("head meta[property=\"{$property}\"]")));
    }
    $tags['canonical'] = array_map(fn ($l) => (string) $l->getAttribute('href'), iterator_to_array($doc->querySelectorAll('head link[rel="canonical"]')));

    return $tags;
}

/**
 * A copy of the fixture parent with the app-home search members added (app-home checks, "the search fixture"):
 * six Câmara mandates and a Senate legislature 58 with three senators, imported like any contract.
 */
function searchFixture(): string
{
    $dir = fixtureCopy();
    $mandate = fn (string $party, string $uf, string $end) => [
        'legislature' => 58, 'party' => $party, 'uf' => $uf,
        'exercisePeriods' => [['start' => '2027-02-01T00:00:00', 'end' => $end]],
        'participation' => ['all' => ['count' => 0, 'total' => 0], 'merit' => ['count' => 0, 'total' => 0]],
        'governmentAlignment' => ['all' => ['count' => 0, 'total' => 0], 'merit' => ['count' => 0, 'total' => 0]],
        'partyAlignment' => ['all' => ['count' => 0, 'total' => 0], 'merit' => ['count' => 0, 'total' => 0]],
        'symbolicMerit' => 0, 'authoredCount' => 0, 'firstSignerCount' => 0, 'requirementsCount' => 0,
    ];
    $member = fn (string $house, int $id, string $name, array $mandate) => [
        'house' => $house, 'id' => $id, 'name' => $name, 'party' => $mandate['party'], 'uf' => $mandate['uf'],
        'photoUrl' => "https://example.org/{$house}/{$id}.jpg", 'sourceUrl' => "https://example.org/{$house}/{$id}",
        'mandates' => [$mandate],
    ];

    $camara = readJson("{$dir}/camara/members.json");
    foreach ([
        [104, 'João da Silva', 'PT', 'SP', '2027-03-01T09:00:00'],
        [105, 'Ágata Rocha', 'PSOL', 'RJ', '2027-03-01T09:00:00'],
        [106, 'Mariana Silva', 'MDB', 'BA', '2027-02-20T00:00:00'],
        [107, 'Abel Nunes', 'PSD', 'AM', '2027-03-01T09:00:00'],
        [108, 'Paulo Silva', 'PL', 'SP', '2027-03-01T09:00:00'],
        [1001, 'Paulo Silva', 'PL', 'MG', '2027-03-01T09:00:00'],
    ] as [$id, $name, $party, $uf, $end]) {
        $camara[] = $member('camara', $id, $name, $mandate($party, $uf, $end));
    }
    writeJson("{$dir}/camara/members.json", $camara);
    $meta = readJson("{$dir}/camara/meta.json");
    $meta['coverage'][1]['members'] = 7;
    writeJson("{$dir}/camara/meta.json", $meta);

    $senado = readJson("{$dir}/senado/members.json");
    foreach ([
        [9107, 'Paulo Silva', 'PT', 'SP', '2027-03-05T12:00:00'],
        [9108, 'Zélia Moura', 'PP', 'GO', '2027-03-05T12:00:00'],
        [9109, 'Otávio Brandão', 'PSD', 'BA', '2027-03-03T00:00:00'],
    ] as [$id, $name, $party, $uf, $end]) {
        $senado[] = $member('senado', $id, $name, $mandate($party, $uf, $end));
    }
    writeJson("{$dir}/senado/members.json", $senado);
    $meta = readJson("{$dir}/senado/meta.json");
    $meta['legislatures'][] = ['id' => 58, 'start' => '2027-02-01', 'end' => '2031-01-31', 'sourceUrl' => 'https://dadosabertos.camara.leg.br/api/v2/legislaturas/58'];
    $meta['coverage'][] = ['legislature' => 58, 'members' => 3, 'rollCalls' => ['nominal' => 0, 'secret' => 0, 'symbolic' => null], 'through' => null, 'unclassified' => 0];
    writeJson("{$dir}/senado/meta.json", $meta);

    return $dir;
}

function importSearchFixture(): void
{
    $result = runImport(['dir' => searchFixture()]);
    expect($result['code'])->toBe(0, $result['err']);
}

/** Text of each element `$selector` matches inside `$root`, in document order. */
function textsOf(?Element $root, string $selector): array
{
    return $root === null ? [] : array_map(fn ($e) => textOf($e), iterator_to_array($root->querySelectorAll($selector)));
}
