<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\ContractImport;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Checks C31-C38 of .specs/features/app-skeleton/checks.md.

beforeEach(function () {
    expect(runImport(['dir' => fixtureDir()])['code'])->toBe(0);
});

test('server renders the page body', function () {
    requireSsr();

    $profile = html($this->get('/deputados/101/'))->getElementById('app');
    expect($profile)->not->toBeNull()
        ->and(textOf($profile->querySelector('h1')))->toBe('Ana Souza')
        ->and(array_map(fn ($b) => textOf($b->querySelector('.ma-ndem__value')), iterator_to_array($profile->querySelectorAll('.ma-ndem'))))->toBe(['4 de 5', '2 de 3', '2 de 3'])
        ->and(array_map(fn ($s) => textOf($s->querySelector('.ma-t-title-1')), iterator_to_array($profile->querySelectorAll('.ma-stat'))))->toBe(['5', '2', '3']);

    $rollCall = html($this->get('/votacoes/100-1/'))->getElementById('app');
    expect($rollCall)->not->toBeNull()
        ->and(textOf($rollCall->querySelector('h1')))->toBe('PL 1/2023')
        ->and(array_map(fn ($dd) => textOf($dd), iterator_to_array($rollCall->querySelectorAll('.ma-tally__counts dd'))))->toBe(['2', '1', '0']);
});

test('head tags survive an ssr outage', function () {
    config(['inertia.ssr.url' => 'http://127.0.0.1:9']);

    foreach (['/deputados/101/' => 'Ana Souza (PT-SP) na 57ª legislatura', '/votacoes/100-1/' => 'PL 1/2023: como cada deputado votou'] as $path => $title) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        $tags = headTags($doc);

        expect($tags['title'])->toBe(["{$title} - Mandato Aberto"])
            ->and($tags['og:title'])->toBe([$title])
            ->and($tags['canonical'])->toBe(["https://mandato.test{$path}"])
            ->and($tags['og:url'])->toBe(["https://mandato.test{$path}"])
            ->and($tags['og:type'])->toBe(['website'])
            ->and($tags['og:site_name'])->toBe(['Mandato Aberto'])
            ->and($tags['og:locale'])->toBe(['pt_BR'])
            ->and($tags['twitter:card'])->toBe(['summary'])
            ->and($tags['description'])->toHaveCount(1)
            ->and($tags['og:description'])->toBe($tags['description'])
            ->and($doc->getElementById('app'))->not->toBeNull()
            ->and($doc->getElementById('app')->childElementCount)->toBe(0);
    }
});

test('public pages set no cookie', function (string $path, int $status) {
    $response = $this->get($path);

    $response->assertStatus($status)->assertHeaderMissing('Set-Cookie');
    expect($response->headers->getCookies())->toBe([]);
})->with([
    ['/deputados/101/', 200],
    ['/votacoes/100-1/', 200],
    ['/deputados/999999/', 404],
    ['/votacoes/999-9/', 404],
    ['/deputados/abc/', 404],
]);

test('footer carries the brasília collection day', function () {
    requireSsr();
    $footer = fn (string $path) => textOf(html($this->get($path))->querySelector('footer'));

    foreach (['/deputados/101/', '/votacoes/100-1/'] as $path) {
        expect($footer($path))->toContain('Dados abertos da Câmara dos Deputados, coletados em 27/09/2026');
    }

    ContractImport::query()->create([
        'schema_version' => 2, 'generated_at' => '2026-10-01T02:30:00Z', 'meta_sha256' => str_repeat('0', 64),
        'members_count' => 3, 'roll_calls_count' => 8, 'votes_count' => 16, 'propositions_count' => 7,
    ]);

    foreach (['/deputados/101/', '/votacoes/100-1/'] as $path) {
        expect($footer($path))->toContain('Dados abertos da Câmara dos Deputados, coletados em 30/09/2026');
    }
});

test('slashless paths answer with the slash canonical', function () {
    foreach (['/deputados/101' => '/deputados/101/', '/votacoes/100-1' => '/votacoes/100-1/'] as $path => $canonical) {
        $response = $this->get($path);
        $response->assertOk();
        expect(headTags(html($response))['canonical'])->toBe(["https://mandato.test{$canonical}"]);
    }
});

test('pages leave the share tags to blade', function () {
    foreach (File::allFiles(resource_path('js')) as $file) {
        expect($file->getContents())->not->toMatch('/import\s*\{[^}]*\bHead\b[^}]*\}\s*from\s*["\']@inertiajs\/vue3["\']/');
    }
    $app = File::get(resource_path('js/app.js'));
    expect($app)->toMatch('/router\.on\(\s*["\']navigate["\']/')
        ->and($app)->toMatch('/document\.title\s*=.*meta\.title/');
});

test('public routes use the cookie-free group', function () {
    foreach (['deputies.show', 'roll-calls.show'] as $name) {
        $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
        expect($middleware)->toContain('public')->not->toContain('web');
    }
    expect(app(Kernel::class)->getMiddlewareGroups()['public'])->toBe([SubstituteBindings::class, HandleInertiaRequests::class]);
});

test('keeps the mvp url shapes', function () {
    expect(Route::getRoutes()->getByName('deputies.show')->wheres)->toBe(['id' => '[0-9]+'])
        ->and(Route::getRoutes()->getByName('roll-calls.show')->wheres)->toBe(['id' => '[0-9]+-[0-9]+'])
        ->and(Route::getRoutes()->getByName('deputies.show')->uri())->toBe('deputados/{id}')
        ->and(Route::getRoutes()->getByName('roll-calls.show')->uri())->toBe('votacoes/{id}');

    $this->get('/deputados/12a/')->assertNotFound();
    $this->get('/votacoes/100/')->assertNotFound();
    $this->get('/votacoes/100-1-2/')->assertNotFound();

    expect(File::get(public_path('.htaccess')))->not->toContain('R=301');
});
