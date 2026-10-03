<?php

use App\Console\Commands\FetchPhotos;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Ssr\HttpGateway;
use Tests\Support\CapturedOutput;
use Tests\Support\Jpeg;
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
 * Runs an Artisan command and returns its exit code, standard output and standard error apart.
 *
 * @param  array<string, mixed>  $parameters
 * @return array{code: int, out: string, err: string}
 */
function runCommand(string $command, array $parameters = []): array
{
    $output = new CapturedOutput;
    $code = Artisan::call($command, $parameters, $output);

    return ['code' => $code, 'out' => $output->fetch(), 'err' => $output->errors()];
}

function camaraPhotoUrl(string $id): string
{
    return "https://www.camara.leg.br/internet/deputado/bandep/{$id}.jpg";
}

function senadoPhotoUrl(string $id, bool $final = false): string
{
    return 'https://'.($final ? 'legis' : 'www').".senado.leg.br/senadores/img/fotos-oficiais/senador{$id}.jpg";
}

/**
 * Fakes the three photo hosts: each deputy answers a 354 x 472 JPEG, each senator a 301 to
 * `legis.senado.leg.br` and then a 480 x 600 JPEG, each seeded by the member id. `$answers` maps
 * a URL to a response (or a callable taking the request) that replaces the default, and may be
 * changed between runs.
 *
 * @param  array<string, mixed>  $answers
 */
function fakePhotoHosts(array &$answers = []): void
{
    // Only the SSR server is reached for real: every other request is faked or refused.
    Http::preventStrayRequests();
    Http::allowStrayRequests([rtrim((string) config('inertia.ssr.url'), '/').'/*']);
    Http::fake(function (Request $request, array $options) use (&$answers) {
        $url = $request->url();
        if (array_key_exists($url, $answers)) {
            $answer = $answers[$url];

            return is_callable($answer) ? $answer($request, $options) : $answer;
        }
        if (preg_match('#^https://www\.camara\.leg\.br/internet/deputado/bandep/(\d+)\.jpg$#', $url, $m) === 1) {
            return Http::response(Jpeg::make(354, 472, (int) $m[1]), 200, ['Content-Type' => 'image/jpeg']);
        }
        if (preg_match('#^https://www\.senado\.leg\.br/senadores/img/fotos-oficiais/senador(\d+)\.jpg$#', $url, $m) === 1) {
            return Http::response('', 301, ['Location' => senadoPhotoUrl($m[1], final: true)]);
        }
        if (preg_match('#^https://legis\.senado\.leg\.br/senadores/img/fotos-oficiais/senador(\d+)\.jpg$#', $url, $m) === 1) {
            return Http::response(Jpeg::make(480, 600, (int) $m[1]), 200, ['Content-Type' => 'image/jpeg']);
        }

        return in_array($request->toPsrRequest()->getUri()->getHost(), FetchPhotos::HOSTS, true) ? Http::response('not found', 404) : null;
    });
}

/** Imports both fixture houses and caches every member's photo through the faked hosts. */
function storeFixturePhotos(): void
{
    importFixtures();
    fakePhotoHosts();
    $result = runCommand('mandato:photos');
    expect($result['code'])->toBe(0, $result['err']);
}

/** @return list<string> the URLs requested so far, in order */
function requestedUrls(): array
{
    return array_map(fn (array $pair) => $pair[0]->url(), Http::recorded()->all());
}
