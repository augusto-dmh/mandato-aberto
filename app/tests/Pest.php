<?php

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

/** The eight tables an import writes. */
const MANDATE_TABLES = ['legislatures', 'members', 'memberships', 'propositions', 'roll_calls', 'votes', 'authorships', 'contract_imports'];

/** The MVP's contract fixture, read in place (plan, Assumptions: test data). */
function fixtureDir(): string
{
    return base_path('../site/tests/fixtures/out');
}

/** A writable copy of the fixture in the system temp directory, for tests that change it. */
function fixtureCopy(): string
{
    $dir = sys_get_temp_dir().'/mandato-contract-'.bin2hex(random_bytes(6));
    File::copyDirectory(fixtureDir(), $dir);

    return $dir;
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

function html(TestResponse $response): Dom\HTMLDocument
{
    return Dom\HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
}

/** Text of an element with whitespace collapsed, leaving out footnote markers. */
function textOf(?Dom\Element $element): string
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
 * The head tags of AC 14 and AC 22, by name.
 *
 * @return array<string, list<string>>
 */
function headTags(Dom\HTMLDocument $doc): array
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
