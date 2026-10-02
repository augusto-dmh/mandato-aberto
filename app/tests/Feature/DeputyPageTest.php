<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Checks C18-C23 of .specs/features/app-skeleton/checks.md.

beforeEach(function () {
    requireSsr();
    expect(runImport(['dir' => fixtureDir()])['code'])->toBe(0);
});

test('profile shows the deputy and the indicators', function () {
    $response = $this->get('/deputados/101/');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('Deputies/Show'));
    $doc = html($response);
    $headings = $doc->querySelectorAll('h1');
    expect($headings)->toHaveCount(1)
        ->and(textOf($headings->item(0)))->toBe('Ana Souza')
        ->and(textOf($doc->querySelector('.ma-hero .ma-eyebrow')))->toBe('Deputado federal · PT · SP');

    $blocks = array_map(fn ($b) => textOf($b->querySelector('.ma-ndem__value')), iterator_to_array($doc->querySelectorAll('.ma-ndem')));
    expect($blocks)->toBe(['4 de 5', '2 de 3', '2 de 3']);

    $stats = array_map(fn ($s) => textOf($s->querySelector('.ma-t-title-1')), iterator_to_array($doc->querySelectorAll('.ma-stat')));
    expect($stats)->toBe(['5', '2', '3']);
});

test('profile lists the votes oldest first', function () {
    $doc = html($this->get('/deputados/101/'));
    $expected = ['100-1', '200-1', '200-2', '100-2', '100-3', '200-3', '100-4', '100-6'];

    $columns = array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($doc->querySelectorAll('.ma-score__col')));
    expect($columns)->toBe(array_map(fn ($id) => "/votacoes/{$id}/", $expected));

    $rows = array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($doc->querySelectorAll('.ma-score__table tbody tr td a')));
    expect($rows)->toBe(array_map(fn ($id) => "/votacoes/{$id}/", $expected));
});

test('profile head carries the share tags', function () {
    $tags = headTags(html($this->get('/deputados/101/')));
    $description = 'Votos, participação em votações nominais e proposições de Ana Souza (PT-SP) na Câmara dos Deputados, com dados oficiais e a base de cada número.';

    expect($tags)->toBe([
        'title' => ['Ana Souza (PT-SP) na 57ª legislatura - Mandato Aberto'],
        'description' => [$description],
        'twitter:card' => ['summary'],
        'og:type' => ['website'],
        'og:site_name' => ['Mandato Aberto'],
        'og:locale' => ['pt_BR'],
        'og:title' => ['Ana Souza (PT-SP) na 57ª legislatura'],
        'og:description' => [$description],
        'og:url' => ['https://mandato.test/deputados/101/'],
        'canonical' => ['https://mandato.test/deputados/101/'],
    ]);
});

test('indicator without base shows no number', function () {
    $doc = html($this->get('/deputados/103/'));
    $blocks = iterator_to_array($doc->querySelectorAll('.ma-ndem'));
    expect($blocks)->toHaveCount(3);

    [$participation, $government, $party] = array_map(fn ($b) => textOf($b->querySelector('.ma-ndem__value')), $blocks);
    expect($participation)->toBe('Sem base de cálculo no período')
        ->and($party)->toBe('Sem base de cálculo no período')
        ->and($participation.$party)->not->toMatch('/\d/')
        ->and($government)->toBe('0 de 1');
});

test('profile draws the initials frame and no remote image', function () {
    $doc = html($this->get('/deputados/101/'));

    expect(textOf($doc->querySelector('.ma-photo__initials')))->toBe('AS')
        ->and($doc->querySelectorAll('img'))->toHaveCount(0);
});

test('profile 404s', function (string $path) {
    // A Senado member with everything a profile needs, so only its house keeps it off /deputados/.
    $senator = DB::table('members')->insertGetId([
        'house' => 'senado', 'source_id' => '555', 'name' => 'Senadora', 'party' => 'P', 'uf' => 'DF',
        'source_url' => 'https://www25.senado.leg.br/', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('memberships')->insert([
        'member_id' => $senator, 'legislature_number' => 57,
        'participation_count' => 1, 'participation_total' => 1, 'government_alignment_count' => 1, 'government_alignment_total' => 1,
        'party_alignment_count' => 1, 'party_alignment_total' => 1, 'authored_count' => 0, 'first_signer_count' => 0, 'requirements_count' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->get($path);

    $response->assertNotFound();
    $doc = html($response);
    expect($doc->documentElement->getAttribute('lang'))->toBe('pt-BR')
        ->and($doc->querySelectorAll('h1'))->toHaveCount(1)
        ->and(textOf($doc->querySelector('h1')))->toBe('Página não encontrada');
})->with(['/deputados/999999/', '/deputados/abc/', '/deputados/555/']);
