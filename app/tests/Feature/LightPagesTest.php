<?php

use App\Http\Middleware\HandleInertiaRequests;
use Carbon\CarbonImmutable;
use Database\Seeders\BudgetSeeder;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\ForbiddenTerms;

// Checks C37-C43, C45-C50 of .specs/features/app-home/checks.md (C44 is tests/budget/first-load.mjs).

test('home search and overview set no cookie', function (string $path, int $status, bool $data) {
    if ($data) {
        importSearchFixture();
    }

    $response = $this->get($path);

    $response->assertStatus($status)->assertHeaderMissing('Set-Cookie');
    expect($response->headers->getCookies())->toBe([]);
})->with([
    ['/', 200, true],
    ['/busca/', 200, true],
    ['/busca/?q=ana&casa=camara', 200, true],
    ['/busca/?q=zzz', 200, true],
    ['/busca/?uf=XX', 200, true],
    ['/legislaturas/57/', 200, true],
    ['/legislaturas/58/', 200, true],
    ['/busca/?pagina=99', 404, true],
    ['/legislaturas/56/', 404, true],
    ['/legislaturas/abc/', 404, true],
    ['/legislaturas/', 404, true],
    ['/', 200, false],
    ['/busca/', 200, false],
    ['/legislaturas/57/', 404, false],
]);

test('light pages render complete html with no script', function () {
    requireSsr();
    importFixtures();
    $cases = [
        '/' => ['O que cada parlamentar federal fez no mandato', ['1 votação nominal no plenário', '1 parlamentar com mandato na legislatura']],
        '/busca/' => ['Buscar parlamentares', ['Ana Souza']],
        '/busca/?situacao=todos' => ['Buscar parlamentares', ['Ana Souza']],
        '/legislaturas/57/' => ['57ª legislatura', [
            '3 votações nominais no plenário', '2 votações secretas no plenário', '1 votação simbólica no plenário',
            '3 parlamentares com mandato na legislatura', '2 proposições apresentadas por parlamentares (PL, PLP, PEC, PDL e PRC)',
            '1 votação nominal no plenário', '1 votação secreta no plenário', 'Votações simbólicas no plenário: não publicadas pela Casa.',
            '6 parlamentares com mandato na legislatura', '1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRS)',
        ]],
    ];
    foreach ($cases as $path => [$h1, $texts]) {
        $response = $this->get($path);
        $content = (string) $response->getContent();
        $doc = html($response);
        $app = $doc->getElementById('app');

        expect($content)->not->toMatch('/<script[^>]*\ssrc=/i')
            ->and($content)->not->toContain('modulepreload')
            ->and(array_map(fn ($l) => $l->getAttribute('href'), iterator_to_array($doc->querySelectorAll('link[rel="stylesheet"]'))))
            ->toHaveCount(1)
            ->and($doc->querySelector('link[rel="stylesheet"]')->getAttribute('href'))->toMatch('#/build/assets/app-[\w-]+\.css$#')
            ->and(textOf($app?->querySelector('h1')))->toBe($h1, $path);
        $counts = textsOf($app, '.ma-count');
        foreach ($texts as $text) {
            expect(in_array($text, $counts, true) || str_contains(textOf($app), $text))->toBeTrue("{$path}: {$text}");
        }
    }
    expect(textsOf(html($this->get('/legislaturas/57/'))->getElementById('app'), '.ma-count'))->toHaveCount(10);
});

test('light pages fall back to the client when ssr is down', function () {
    importFixtures();
    config(['inertia.ssr.url' => 'http://127.0.0.1:9']);
    $pages = [
        '/' => ['O que cada parlamentar federal fez no mandato', '/', null],
        '/busca/' => ['Buscar parlamentares', '/busca/', 'noindex, follow'],
        '/legislaturas/57/' => ['57ª legislatura: votações e proposições na Câmara e no Senado', '/legislaturas/57/', null],
    ];
    foreach ($pages as $path => [$title, $canonical, $robots]) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        $tags = headTags($doc);

        expect($tags['title'])->toBe(["{$title} - Mandato Aberto"])
            ->and($tags['og:title'])->toBe([$title])
            ->and($tags['description'])->toHaveCount(1)
            ->and($tags['og:description'])->toBe($tags['description'])
            ->and($tags['canonical'])->toBe(["https://mandato.test{$canonical}"])
            ->and($tags['og:url'])->toBe(["https://mandato.test{$canonical}"])
            ->and($tags['og:type'])->toBe(['website'])
            ->and($tags['twitter:card'])->toBe(['summary'])
            ->and(array_map(fn ($m) => $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll('meta[name="robots"]'))))
            ->toBe($robots === null ? [] : [$robots])
            ->and($doc->getElementById('app'))->not->toBeNull()
            ->and($doc->getElementById('app')->childElementCount)->toBe(0)
            ->and($doc->querySelector('script[type="module"][src]')?->getAttribute('src'))->toMatch('#/build/assets/app-[\w-]+\.js$#');
    }
});

test('slashless home paths answer with the slash canonical', function () {
    importFixtures();

    foreach (['/busca' => '/busca/', '/legislaturas/57' => '/legislaturas/57/'] as $path => $canonical) {
        $response = $this->get($path);
        $response->assertOk();
        expect(headTags(html($response))['canonical'])->toBe(["https://mandato.test{$canonical}"], $path)
            ->and(headTags(html($this->get($canonical)))['canonical'])->toBe(["https://mandato.test{$canonical}"]);
    }
});

/** AC 38's words beyond the skeleton list. */
const OVERVIEW_WORDS = ['importante', 'importantes', 'relevante', 'relevantes', 'mais votado', 'menos votado', 'posição'];

test('no ranking word on home search or overview', function () {
    requireSsr();
    $terms = [...OVERVIEW_WORDS, ...config('forbidden-terms')];
    expect($terms)->toHaveCount(26);
    $scan = function (string $path) use ($terms) {
        $response = $this->get($path);
        $response->assertOk();
        $text = html_entity_decode((string) $response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            .json_encode($response->viewData('page')['props'], JSON_UNESCAPED_UNICODE);
        expect(ForbiddenTerms::inText($text, $terms))->toBe([], $path);
    };

    $scan('/');
    importSearchFixture();
    foreach (['/', '/busca/', '/busca/?situacao=todos', '/busca/?q=zzz', '/legislaturas/57/', '/legislaturas/58/'] as $path) {
        $scan($path);
    }

    expect(ForbiddenTerms::inText('Sua POSICAO', OVERVIEW_WORDS))->toBe(['posição'])
        ->and(ForbiddenTerms::inText('o mais  votado', OVERVIEW_WORDS))->toBe(['mais votado'])
        ->and(ForbiddenTerms::inText('a composição', OVERVIEW_WORDS))->toBe([]);
});

/** @return list<string> the stylesheet files a page links, as paths under public/ */
function linkedStylesheets(HTMLDocument $doc): array
{
    return array_map(fn ($l) => public_path(ltrim((string) parse_url((string) $l->getAttribute('href'), PHP_URL_PATH), '/')),
        iterator_to_array($doc->querySelectorAll('link[rel="stylesheet"]')));
}

test('light pages stay within the html budget', function () {
    requireSsr();
    $this->seed(BudgetSeeder::class);

    foreach (['/' => 20000, '/busca/' => 25000, '/legislaturas/58/' => 30000] as $path => $budget) {
        $response = $this->get($path);
        $response->assertOk();
        $html = (string) $response->getContent();
        expect(html($response)->getElementById('app')?->childElementCount)->toBeGreaterThan(0)
            ->and(strlen((string) gzencode($html, 6)))->toBeLessThanOrEqual($budget, $path);
    }
});

test('light pages stay within the css budget', function () {
    requireSsr();
    $this->seed(BudgetSeeder::class);

    foreach (['/', '/busca/', '/legislaturas/58/'] as $path) {
        $files = linkedStylesheets(html($this->get($path)));
        expect($files)->not->toBeEmpty();
        $total = array_sum(array_map(fn ($f) => strlen((string) gzencode((string) file_get_contents($f), 6)), $files));
        expect($total)->toBeLessThanOrEqual(8000, $path);
    }
});

test('hydrated pages keep their head', function () {
    requireSsr();
    importFixtures();
    $expected = [
        'meta[charset]', 'meta[name=viewport]', 'title', 'meta[name=description]', 'link[rel=canonical]',
        'meta[property=og:type]', 'meta[property=og:site_name]', 'meta[property=og:locale]', 'meta[property=og:title]',
        'meta[property=og:description]', 'meta[property=og:url]', 'meta[name=twitter:card]',
        'link[rel=preload]', 'link[rel=modulepreload]', 'link[rel=stylesheet]', 'script[type=module]',
    ];
    foreach (['/metodologia/', '/deputados/101/'] as $path) {
        $doc = html($this->get($path));
        $head = array_map(function ($e) {
            $tag = strtolower($e->tagName);
            foreach (['charset' => null, 'name' => 1, 'property' => 1, 'rel' => 1, 'type' => 1] as $attr => $withValue) {
                if ($e->hasAttribute($attr)) {
                    return $withValue === null ? "{$tag}[{$attr}]" : "{$tag}[{$attr}=".$e->getAttribute($attr).']';
                }
            }

            return $tag;
        }, iterator_to_array($doc->querySelector('head')->children));

        expect($head)->toBe($expected, $path)
            ->and($doc->querySelectorAll('meta[name="robots"]'))->toHaveCount(0)
            ->and($doc->querySelector('script[type="module"]')->getAttribute('src'))->toMatch('#/build/assets/app-[\w-]+\.js$#');
    }
});

test('light pages answer inertia visits with json', function (string $path, string $component) {
    importFixtures();
    $version = (string) app(HandleInertiaRequests::class)->version(request());

    $response = $this->get($path, ['X-Inertia' => 'true', 'X-Inertia-Version' => $version]);

    $response->assertOk()->assertHeader('X-Inertia', 'true');
    expect($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($response->json('component'))->toBe($component)
        ->and($response->json('props.meta.title'))->toBeString()->not->toBeEmpty();
})->with([
    'home' => ['/', 'Home/Index'],
    'search' => ['/busca/', 'Search/Index'],
    'overview' => ['/legislaturas/57/', 'Legislatures/Show'],
]);

test('home routes use the cookie-free group', function () {
    $routes = [
        'home' => ['/', []],
        'search' => ['busca', []],
        'legislatures.show' => ['legislaturas/{n}', ['n' => '[0-9]+']],
    ];
    foreach ($routes as $name => [$uri, $wheres]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull($name)
            ->and($route->gatherMiddleware())->toContain('public')->not->toContain('web')
            ->and($route->uri())->toBe($uri)
            ->and($route->wheres)->toBe($wheres);
    }
    $uris = array_map(fn ($r) => $r->uri(), Route::getRoutes()->getRoutes());
    expect($uris)->not->toContain('legislaturas');
});

test('budget dataset matches the plan', function () {
    $this->seed(BudgetSeeder::class);
    $memberships = fn (string $house, int $n) => DB::table('memberships')->join('members', 'members.id', '=', 'memberships.member_id')
        ->where('members.house', $house)->where('memberships.legislature_number', $n);

    expect(DB::table('legislatures')->orderBy('number')->pluck('number')->all())->toBe([57, 58])
        ->and($memberships('camara', 58)->count())->toBe(600)
        ->and($memberships('senado', 58)->count())->toBe(81)
        ->and($memberships('camara', 57)->count())->toBe(300)
        ->and($memberships('senado', 57)->count())->toBe(40)
        ->and(DB::table('memberships')->where('legislature_number', 58)->distinct()->count('party'))->toBe(30);

    // Every membership of 58 has a period ending on or after the Brasília day of its house's import (2031-01-31).
    $out = DB::table('memberships')->where('legislature_number', 58)
        ->whereNotExists(fn ($q) => $q->from('exercise_periods')->whereColumn('exercise_periods.membership_id', 'memberships.id')
            ->where('exercise_periods.ends_at', '>=', '2031-01-31 00:00:00'))
        ->count();
    expect($out)->toBe(0);

    $lengths = DB::table('members')->pluck('name')->map(fn ($n) => mb_strlen($n));
    expect($lengths->min())->toBeGreaterThanOrEqual(10)
        ->and($lengths->max())->toBe(40);

    foreach (['camara', 'senado'] as $house) {
        $plenary = DB::table('roll_calls')->where('house', $house)->where('legislature_number', 58)->where('organ', 'PLEN');
        expect((clone $plenary)->where('ballot', 'nominal')->count())->toBe(1350, $house)
            ->and((clone $plenary)->where('ballot', 'secret')->count())->toBe(150, $house)
            ->and((clone $plenary)->whereIn('ballot', ['nominal', 'secret'])->selectRaw("to_char(date, 'YYYY-MM') as m")->distinct()->orderBy('m')->pluck('m')->all())
            ->toBe(array_map(fn ($k) => CarbonImmutable::create(2027, 2, 1)->addMonths($k)->format('Y-m'), range(0, 47)));
        $import = DB::table('contract_imports')->where('house', $house)->get();
        expect($import)->toHaveCount(1)
            ->and(CarbonImmutable::parse($import[0]->generated_at, 'UTC')->format('Y-m-d\TH:i:s\Z'))->toBe('2031-01-31T15:00:00Z')
            ->and(array_column(json_decode($import[0]->coverage, true), 'legislature'))->toBe([57, 58]);
    }
    expect(DB::table('roll_calls')->where('house', 'camara')->where('organ', 'PLEN')->where('ballot', 'symbolic')->count())->toBe(500)
        ->and(DB::table('roll_calls')->where('house', 'senado')->where('ballot', 'symbolic')->count())->toBe(0);
});

test('home search and overview footers name both houses', function () {
    requireSsr();
    $footer = fn (string $path) => textsOf(html($this->get($path))->querySelector('footer'), 'p');

    $result = runImport(['dir' => fixtureDir('camara')]);
    expect($result['code'])->toBe(0, $result['err']);
    foreach (['/', '/busca/', '/legislaturas/57/'] as $path) {
        expect($footer($path))->toBe(['Dados abertos da Câmara dos Deputados, coletados em 01/03/2027.'], $path);
    }

    $result = runImport(['dir' => fixtureDir('senado')]);
    expect($result['code'])->toBe(0, $result['err']);
    foreach (['/', '/busca/', '/legislaturas/57/'] as $path) {
        expect($footer($path))->toBe(['Dados abertos da Câmara dos Deputados, coletados em 01/03/2027, e do Senado Federal, coletados em 05/03/2027.'], $path);
    }
    expect(textOf(html($this->get('/deputados/101/'))->querySelector('footer')))->toContain('Dados abertos da Câmara dos Deputados, coletados em 01/03/2027')
        ->not->toContain('Senado');
});

test('home search and overview footers name both houses, one or none imported', function () {
    requireSsr();
    $footer = fn (string $path) => textsOf(html($this->get($path))->querySelector('footer'), 'p');

    expect($footer('/'))->toBe([])
        ->and($footer('/busca/'))->toBe([]);

    $result = runImport(['dir' => fixtureDir('senado')]);
    expect($result['code'])->toBe(0, $result['err']);
    foreach (['/', '/busca/', '/legislaturas/57/'] as $path) {
        expect($footer($path))->toBe(['Dados abertos do Senado Federal, coletados em 05/03/2027.'], $path);
    }
});

test('every public page links home and to search', function () {
    requireSsr();
    importFixtures();

    foreach ([...RENDERED_PAGES, '/', '/busca/', '/legislaturas/57/'] as $path) {
        $header = html($this->get($path))->querySelector('header');
        $links = array_map(fn ($a) => [textOf($a), $a->getAttribute('href')], iterator_to_array($header?->querySelectorAll('a') ?? []));
        expect(in_array(['Mandato Aberto', '/'], $links, true))->toBeTrue($path)
            ->and(in_array(['Buscar parlamentar', '/busca/'], $links, true))->toBeTrue($path)
            ->and(textOf($header->querySelector('a.ma-wordmark')))->toBe('Mandato Aberto');
    }
    expect(count(RENDERED_PAGES) + 3)->toBe(19);
});
