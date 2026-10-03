<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\PublicUrl;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Checks C67-C72 of .specs/features/app-contract-v3/checks.md (they carry skeleton C31-C33, C35-C38).

beforeEach(function () {
    importFixtures();
});

test('server renders every new page body', function () {
    requireSsr();
    $app = fn (string $path) => html($this->get($path))->getElementById('app');

    $profile = $app('/deputados/101/legislatura/57/');
    expect($profile)->not->toBeNull()
        ->and(textOf($profile->querySelector('h1')))->toBe('Ana Souza')
        ->and(array_map(fn ($v) => textOf($v), iterator_to_array($profile->querySelectorAll('.ma-ndem .ma-ndem__value'))))
        ->toBe(['3 de 4', '7 de 10', '2 de 3', '4 de 6', '1 de 2', '5 de 8']);

    foreach (['/senadores/9101/legislatura/57/' => 'Rosa Andrade', '/votacoes/100-1/' => 'PL 1/2023', '/senado/votacoes/6923/' => 'PL 1/2025'] as $path => $heading) {
        expect(textOf($app($path)?->querySelector('h1')))->toBe($heading, $path);
    }

    $method = $app('/metodologia/');
    expect(textOf($method->querySelector('h1')))->toBe('Metodologia');
    $rows = array_map(fn ($tr) => array_map(fn ($td) => textOf($td), iterator_to_array($tr->querySelectorAll('td'))),
        iterator_to_array($method->querySelector('#cobertura')->querySelectorAll('tbody tr')));
    expect($rows)->toBe([
        ['Câmara dos Deputados', '57ª', '01/09/2025', '4', '2', '1', '1'],
        ['Câmara dos Deputados', '58ª', '15/02/2027', '1', '0', '0', '0'],
        ['Senado Federal', '57ª', '10/06/2025', '1', '1', 'não publicadas pela Casa', '1'],
    ]);
});

test('head tags survive an ssr outage', function () {
    config(['inertia.ssr.url' => 'http://127.0.0.1:9']);

    $pages = [
        '/deputados/101/legislatura/57/' => ['Ana Souza (PSB-SP) na 57ª legislatura', '/deputados/101/legislatura/57/'],
        '/senadores/9101/' => ['Rosa Andrade (PT-SP) na 57ª legislatura', '/senadores/9101/'],
        '/senado/votacoes/6923/' => ['PL 1/2025: como cada senador votou', '/senado/votacoes/6923/'],
        '/metodologia/' => ['Metodologia', '/metodologia/'],
    ];
    foreach ($pages as $path => [$title, $canonical]) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        $tags = headTags($doc);

        expect($tags['title'])->toBe(["{$title} - Mandato Aberto"])
            ->and($tags['og:title'])->toBe([$title])
            ->and($tags['canonical'])->toBe(["https://mandato.test{$canonical}"])
            ->and($tags['og:url'])->toBe(["https://mandato.test{$canonical}"])
            ->and($tags['og:type'])->toBe(['website'])
            ->and($tags['og:site_name'])->toBe(['Mandato Aberto'])
            ->and($tags['og:locale'])->toBe(['pt_BR'])
            ->and($tags['twitter:card'])->toBe(['summary'])
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
    ['/deputados/101/legislatura/57/', 200],
    ['/senadores/9101/', 200],
    ['/senadores/9101/legislatura/57/', 200],
    ['/votacoes/100-1/', 200],
    ['/senado/votacoes/6923/', 200],
    ['/metodologia/', 200],
    ['/deputados/999999/', 404],
    ['/deputados/101/legislatura/56/', 404],
    ['/senadores/101/', 404],
    ['/senadores/9101/legislatura/58/', 404],
    ['/votacoes/999-9/', 404],
    ['/senado/votacoes/100-1/', 404],
    ['/senado/votacoes/9999/', 404],
    ['/deputados/abc/', 404],
]);

test('slashless paths answer with the slash canonical', function () {
    $cases = [
        '/deputados/101' => '/deputados/101/',
        '/deputados/101/legislatura/57' => '/deputados/101/legislatura/57/',
        '/senadores/9101' => '/senadores/9101/',
        '/senadores/9101/legislatura/57' => '/senadores/9101/',
        '/votacoes/100-1' => '/votacoes/100-1/',
        '/senado/votacoes/6923' => '/senado/votacoes/6923/',
        '/metodologia' => '/metodologia/',
    ];
    foreach ($cases as $path => $canonical) {
        $response = $this->get($path);
        $response->assertOk();
        expect(headTags(html($response))['canonical'])->toBe(["https://mandato.test{$canonical}"], $path);
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
    $routes = [
        'deputies.show' => ['deputados/{id}', ['id' => '[0-9]+']],
        'deputies.legislature' => ['deputados/{id}/legislatura/{n}', ['id' => '[0-9]+', 'n' => '[0-9]+']],
        'senators.show' => ['senadores/{id}', ['id' => '[0-9]+']],
        'senators.legislature' => ['senadores/{id}/legislatura/{n}', ['id' => '[0-9]+', 'n' => '[0-9]+']],
        'roll-calls.show' => ['votacoes/{id}', ['id' => '[0-9]+-[0-9]+']],
        'senate-roll-calls.show' => ['senado/votacoes/{id}', ['id' => '[0-9]+']],
        'methodology' => ['metodologia', []],
    ];
    foreach ($routes as $name => [$uri, $wheres]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull($name)
            ->and($route->gatherMiddleware())->toContain('public')->not->toContain('web')
            ->and($route->uri())->toBe($uri)
            ->and($route->wheres)->toBe($wheres);
    }
    expect(app(Kernel::class)->getMiddlewareGroups()['public'])->toBe([SubstituteBindings::class, HandleInertiaRequests::class])
        ->and(File::get(public_path('.htaccess')))->not->toContain('R=301');
});

test('public urls have one builder', function () {
    expect(PublicUrl::member('senado', '9101'))->toBe('/senadores/9101/')
        ->and(PublicUrl::member('camara', '101', 57))->toBe('/deputados/101/legislatura/57/')
        ->and(PublicUrl::member('camara', '101'))->toBe('/deputados/101/')
        ->and(PublicUrl::member('senado', '9101', 57))->toBe('/senadores/9101/legislatura/57/')
        ->and(PublicUrl::rollCall('senado', '6923'))->toBe('/senado/votacoes/6923/')
        ->and(PublicUrl::rollCall('camara', '100-1'))->toBe('/votacoes/100-1/');

    $literal = '/["\'`]\/(deputados|senadores|votacoes|senado\/votacoes)\//';
    $files = [...File::allFiles(app_path()), ...File::allFiles(resource_path('js'))];
    foreach ($files as $file) {
        if ($file->getRealPath() === realpath(app_path('Support/PublicUrl.php'))) {
            continue;
        }
        expect(preg_match($literal, $file->getContents()))->toBe(0, $file->getRelativePathname());
    }
});
