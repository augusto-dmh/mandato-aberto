<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Checks C24 and C26-C30 of .specs/features/app-skeleton/checks.md.

beforeEach(function () {
    requireSsr();
    expect(runImport(['dir' => fixtureDir()])['code'])->toBe(0);
});

/**
 * An open Câmara roll call in legislature 57 with one vote per entry, each by a new member.
 *
 * @param  array<int, array{0: string, 1: string}>  $votes  [member source id => [name, vote]]
 */
function seedRollCall(string $id, array $votes): void
{
    $rollCall = DB::table('roll_calls')->insertGetId([
        'house' => 'camara', 'source_id' => $id, 'legislature_number' => 57, 'date' => '2026-05-04', 'organ' => 'PLEN',
        'description' => "Votação {$id}", 'approved' => true, 'secret' => false, 'tally_yes' => 1, 'tally_no' => 1, 'tally_others' => 0,
        'source_url' => "https://dadosabertos.camara.leg.br/api/v2/votacoes/{$id}", 'created_at' => now(), 'updated_at' => now(),
    ]);
    foreach ($votes as $sourceId => [$name, $vote]) {
        $member = DB::table('members')->insertGetId([
            'house' => 'camara', 'source_id' => (string) $sourceId, 'name' => $name, 'party' => 'P', 'uf' => 'SP',
            'source_url' => "https://www.camara.leg.br/deputados/{$sourceId}", 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('votes')->insert([
            'roll_call_id' => $rollCall, 'member_id' => $member, 'vote' => $vote, 'party' => 'P', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

test('roll call shows heading result and tally', function () {
    $response = $this->get('/votacoes/100-1/');
    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('RollCalls/Show'));
    $doc = html($response);
    expect(textOf($doc->querySelector('h1')))->toBe('PL 1/2023')
        ->and(textOf($doc->querySelector('.ma-result .ma-t-title-1')))->toBe('Aprovada')
        ->and(array_map(fn ($dd) => textOf($dd), iterator_to_array($doc->querySelectorAll('.ma-tally__counts dd'))))->toBe(['2', '1', '0']);

    expect(textOf(html($this->get('/votacoes/100-4/'))->querySelector('h1')))->toBe('Votação nominal de 01/06/2025')
        ->and(textOf(html($this->get('/votacoes/100-2/'))->querySelector('.ma-result .ma-t-title-1')))->toBe('Rejeitada')
        ->and(textOf(html($this->get('/votacoes/100-3/'))->querySelector('.ma-result .ma-t-title-1')))->toBe('Resultado não informado');
});

test('roll call groups every deputy by vote', function () {
    seedRollCall('900-1', [
        901 => ['Zeca', 'Zeta'], 902 => ['Vera', ''], 903 => ['Ulisses', 'Artigo 17'], 904 => ['Tânia', 'Beta'],
        905 => ['Saulo', 'Obstrução'], 906 => ['Rita', 'Abstenção'], 907 => ['Quintino', 'Não'], 908 => ['Paula', 'Sim'],
    ]);

    $doc = html($this->get('/votacoes/900-1/'));
    $groups = iterator_to_array($doc->querySelectorAll('.ma-group'));
    $expected = [['Sim', 908], ['Não', 907], ['Abstenção', 906], ['Obstrução', 905], ['Artigo 17', 903], ['', 902], ['Beta', 904], ['Zeta', 901]];

    expect($groups)->toHaveCount(8);
    foreach ($groups as $i => $group) {
        [$vote, $deputy] = $expected[$i];
        $rows = iterator_to_array($group->querySelectorAll('.ma-group__list li'));
        expect($rows)->toHaveCount(1)
            ->and($rows[0]->querySelector('svg.ma-vote'))->not->toBeNull()
            ->and($rows[0]->querySelector('a')?->getAttribute('href'))->toBe("/deputados/{$deputy}/");
        if ($vote !== '') {
            expect($rows[0]->querySelector('svg.ma-vote')->getAttribute('aria-label'))->toContain($vote === 'Artigo 17' ? 'Art. 17' : $vote);
        }
    }
});

test('secret roll call lists who voted', function () {
    $doc = html($this->get('/votacoes/100-6/'));
    $groups = $doc->querySelectorAll('.ma-group');

    expect($groups)->toHaveCount(1)
        ->and(textOf($groups->item(0)->querySelector('.ma-group__head')))->toStartWith('Deputados que votaram')
        ->and(array_map(fn ($a) => textOf($a), iterator_to_array($groups->item(0)->querySelectorAll('li a'))))->toBe(['Ana Souza', 'Bruno Lima', 'Carla Dias']);
});

test('roll call without votes says so', function () {
    seedRollCall('900-2', []);

    $doc = html($this->get('/votacoes/900-2/'));

    expect((string) $doc->body->textContent)->toContain('Nenhum voto individual registrado nesta votação')
        ->and($doc->querySelectorAll('.ma-group'))->toHaveCount(0);
});

test('roll call head carries the share tags', function () {
    $open = headTags(html($this->get('/votacoes/100-1/')));
    $description = 'Votação nominal de 01/03/2023 (Plenário) na Câmara dos Deputados, com o voto de cada deputado e o registro oficial.';
    expect($open)->toBe([
        'title' => ['PL 1/2023: como cada deputado votou - Mandato Aberto'],
        'description' => [$description],
        'twitter:card' => ['summary'],
        'og:type' => ['website'],
        'og:site_name' => ['Mandato Aberto'],
        'og:locale' => ['pt_BR'],
        'og:title' => ['PL 1/2023: como cada deputado votou'],
        'og:description' => [$description],
        'og:url' => ['https://mandato.test/votacoes/100-1/'],
        'canonical' => ['https://mandato.test/votacoes/100-1/'],
    ]);

    $secret = headTags(html($this->get('/votacoes/100-6/')));
    expect($secret['title'])->toBe(['Votação nominal de 01/08/2025: votação secreta - Mandato Aberto'])
        ->and($secret['description'])->toBe(['Votação secreta de 01/08/2025 (Plenário) na Câmara dos Deputados, com os totais oficiais e os deputados que votaram.'])
        ->and($secret['og:url'])->toBe(['https://mandato.test/votacoes/100-6/']);

    expect(headTags(html($this->get('/votacoes/200-1/')))['description'][0])->toContain('(CCJC)');
});

test('roll call 404s', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
    $doc = html($response);
    expect($doc->documentElement->getAttribute('lang'))->toBe('pt-BR')
        ->and($doc->querySelectorAll('h1'))->toHaveCount(1)
        ->and(textOf($doc->querySelector('h1')))->toBe('Página não encontrada');
})->with(['/votacoes/999-9/', '/votacoes/abc/']);
