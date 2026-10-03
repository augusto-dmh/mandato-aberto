<?php

use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Checks C51-C53, C55-C59, C76 and C77 of .specs/features/app-contract-v3/checks.md.

beforeEach(function () {
    requireSsr();
    importFixtures();
});

/** Each `.ma-group`: its heading (without the count), names, links, row labels and Senate notes. */
function groupsOf(HTMLDocument $doc): array
{
    return array_map(function ($g) {
        $head = $g->querySelector('.ma-group__head')->cloneNode(true);
        $head->querySelector('.ma-num')?->remove();
        $rows = iterator_to_array($g->querySelectorAll('.ma-group__list li'));

        return [
            'label' => textOf($head),
            'names' => array_map(fn ($li) => textOf($li->querySelector('a')), $rows),
            'hrefs' => array_map(fn ($li) => $li->querySelector('a')->getAttribute('href'), $rows),
            'marks' => array_map(fn ($li) => (string) $li->querySelector('svg.ma-vote')?->getAttribute('aria-label'), $rows),
            'notes' => array_map(fn ($li) => textOf($li->querySelector('.ma-group__official.ma-muted')), $rows),
        ];
    }, iterator_to_array($doc->querySelectorAll('.ma-group')));
}

test('senate roll call page lists every senator', function () {
    $response = $this->get('/senado/votacoes/6923/');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('RollCalls/Show'));
    $doc = html($response);
    expect(textOf($doc->querySelector('h1')))->toBe('PL 1/2025');
    $hrefs = array_merge(...array_column(groupsOf($doc), 'hrefs'));
    sort($hrefs);
    expect($hrefs)->toBe(array_map(fn ($id) => "/senadores/{$id}/", range(9101, 9106)));
});

test('roll call heading names the proposition or the ballot', function () {
    $cases = [
        '/votacoes/100-1/' => 'PL 1/2023',
        '/votacoes/100-3/' => 'PL 1/2023',
        '/votacoes/100-4/' => 'PEC 3/2024',
        '/votacoes/100-2/' => 'Votação nominal de 10/05/2023',
        '/votacoes/100-5/' => 'Votação secreta de 01/08/2025',
        '/senado/votacoes/7001/' => 'Votação secreta de 10/06/2025',
    ];
    foreach ($cases as $path => $heading) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        expect($doc->querySelectorAll('h1'))->toHaveCount(1)
            ->and(textOf($doc->querySelector('h1')))->toBe($heading, $path);
    }
});

test('roll call names its ballot kind and rule', function () {
    $cases = [
        '/votacoes/100-1/' => ['Votação nominal · Decisão sobre a proposta', 'regra camara.09', '/metodologia/#regra-camara-09'],
        '/votacoes/100-2/' => ['Votação nominal · Emenda, destaque ou parte do texto', 'regra camara.08', '/metodologia/#regra-camara-08'],
        '/votacoes/100-3/' => ['Votação nominal · Procedimento', 'regra camara.04', '/metodologia/#regra-camara-04'],
        '/votacoes/100-4/' => ['Votação simbólica · Decisão sobre a proposta', 'regra camara.09', '/metodologia/#regra-camara-09'],
        '/votacoes/100-6/' => ['Votação secreta · Sem regra correspondente', 'como as votações são classificadas', '/metodologia/#classificacao'],
        '/senado/votacoes/7001/' => ['Votação secreta · Decisão sobre a proposta', 'regra senado.01', '/metodologia/#regra-senado-01'],
    ];
    foreach ($cases as $path => [$line, $label, $href]) {
        $kind = html($this->get($path))->querySelector('.ma-rollcall__kind');
        expect(textOf($kind->querySelector('.ma-rollcall__classification')))->toBe($line, $path)
            ->and(textOf($kind))->toBe("{$line} · {$label}", $path)
            ->and(textOf($kind->querySelector('a')))->toBe($label)
            ->and($kind->querySelector('a')->getAttribute('href'))->toBe($href);
    }
});

test('roll call groups every member by position', function () {
    $nominal = groupsOf(html($this->get('/senado/votacoes/6923/')));
    expect(array_column($nominal, 'label'))->toBe(['Sim', 'Não', 'Presidente da sessão (art. 51 RISF)', 'Sem voto registrado'])
        ->and(array_column($nominal, 'names'))->toBe([['Rosa Andrade'], ['Sérgio Prado'], ['Ubiratan Costa'], ['Teresa Lins', 'Vera Dantas', 'Wagner Reis']])
        ->and($nominal[3]['marks'])->toBe([
            'Teresa Lins, MDB-BA, Sem voto: Presente, não registrou voto',
            'Vera Dantas, PP-GO, Sem voto: Licença',
            'Wagner Reis, PSB-PE, Sem voto: Não compareceu',
        ])
        ->and($nominal[3]['notes'])->toBe([
            'Registro do Senado: Presente, não registrou voto', 'Registro do Senado: Licença', 'Registro do Senado: Não compareceu',
        ])
        ->and($nominal[2]['marks'])->toBe(['Ubiratan Costa, PSD-AM, Presidente da sessão (art. 51 RISF)'])
        ->and($nominal[0]['notes'])->toBe(['']);

    $secret = groupsOf(html($this->get('/senado/votacoes/7001/')));
    expect(array_column($secret, 'label'))->toBe(['Senadores que votaram', 'Sem voto registrado'])
        ->and($secret[0]['names'])->toBe(['Rosa Andrade', 'Sérgio Prado'])
        ->and($secret[0]['marks'])->toBe(['Rosa Andrade, PT-SP, Votou (votação secreta)', 'Sérgio Prado, PL-RJ, Votou (votação secreta)'])
        ->and($secret[1]['names'])->toBe(['Teresa Lins', 'Ubiratan Costa', 'Vera Dantas', 'Wagner Reis'])
        ->and($secret[1]['notes'])->toBe([
            'Registro do Senado: Licença', 'Registro do Senado: Atividade parlamentar',
            'Registro do Senado: Missão da Casa no País ou no exterior', 'Registro do Senado: Dispositivo não citado',
        ]);

    $camara = groupsOf(html($this->get('/votacoes/100-2/')));
    expect(array_column($camara, 'label'))->toBe(['Abstenção', 'Obstrução', 'Sem voto registrado'])
        ->and($camara[2]['names'])->toBe(['Carla Dias'])
        ->and($camara[2]['marks'])->toBe(['Carla Dias, MDB-MG, Registro sem voto'])
        ->and($camara[2]['notes'])->toBe([''])
        ->and(array_merge(...array_column($camara, 'hrefs')))->toBe(['/deputados/101/', '/deputados/102/', '/deputados/103/']);
    expect((string) $this->get('/votacoes/100-2/')->getContent())->not->toContain('Registro do Senado');

    $hidden = groupsOf(html($this->get('/votacoes/100-6/')));
    expect(array_column($hidden, 'label'))->toBe(['Deputados que votaram'])
        ->and($hidden[0]['names'])->toBe(['Ana Souza', 'Bruno Lima', 'Carla Dias']);
});

test('symbolic roll call has no tally and no group', function () {
    $doc = html($this->get('/votacoes/100-4/'));

    expect(textOf($doc->querySelector('main')))->toContain('Votação simbólica: não há registro do voto de cada parlamentar nem placar.')
        ->and($doc->querySelectorAll('.ma-tally'))->toHaveCount(0)
        ->and($doc->querySelectorAll('.ma-group'))->toHaveCount(0);
});

test('secret roll call without a tally says so', function () {
    $doc = html($this->get('/votacoes/100-5/'));
    $main = textOf($doc->querySelector('main'));
    expect($main)->toContain('Placar não publicado pela Casa.')
        ->and($main)->toContain('Nenhum voto individual registrado nesta votação')
        ->and($doc->querySelectorAll('.ma-tally'))->toHaveCount(0);

    $tallied = html($this->get('/votacoes/100-6/'));
    expect(array_map(fn ($dd) => textOf($dd), iterator_to_array($tallied->querySelectorAll('.ma-tally__counts dd'))))->toBe(['2', '1', '0'])
        ->and(textOf($tallied->querySelector('main')))->not->toContain('Placar não publicado pela Casa.');
});

test('roll call head carries the share tags by ballot', function () {
    $senate = headTags(html($this->get('/senado/votacoes/6923/')));
    expect($senate['title'])->toBe(['PL 1/2025: como cada senador votou - Mandato Aberto'])
        ->and($senate['og:title'])->toBe(['PL 1/2025: como cada senador votou'])
        ->and($senate['canonical'])->toBe(['https://mandato.test/senado/votacoes/6923/'])
        ->and($senate['og:url'])->toBe(['https://mandato.test/senado/votacoes/6923/'])
        ->and($senate['og:type'])->toBe(['website'])
        ->and($senate['og:site_name'])->toBe(['Mandato Aberto'])
        ->and($senate['og:locale'])->toBe(['pt_BR'])
        ->and($senate['twitter:card'])->toBe(['summary'])
        ->and($senate['description'])->toHaveCount(1)
        ->and($senate['og:description'])->toBe($senate['description']);

    expect(headTags(html($this->get('/senado/votacoes/7001/')))['title'])->toBe(['Votação secreta de 10/06/2025: votação secreta - Mandato Aberto'])
        ->and(headTags(html($this->get('/votacoes/100-4/')))['title'])->toBe(['PEC 3/2024: votação simbólica - Mandato Aberto']);

    $camara = headTags(html($this->get('/votacoes/100-1/')));
    expect($camara['title'])->toBe(['PL 1/2023: como cada deputado votou - Mandato Aberto'])
        ->and($camara['description'])->toBe(['Votação nominal de 01/03/2023 (Plenário) na Câmara dos Deputados, com o voto de cada deputado e o registro oficial.'])
        ->and(headTags(html($this->get('/votacoes/100-6/')))['title'])->toBe(['Votação secreta de 01/09/2025: votação secreta - Mandato Aberto'])
        ->and(headTags(html($this->get('/votacoes/200-1/')))['description'][0])->toContain('(CCJC)');
});

test('roll call pages 404', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
    $doc = html($response);
    expect($doc->documentElement->getAttribute('lang'))->toBe('pt-BR')
        ->and($doc->querySelectorAll('h1'))->toHaveCount(1)
        ->and(textOf($doc->querySelector('h1')))->toBe('Página não encontrada');
})->with(['/votacoes/999-9/', '/votacoes/abc/', '/votacoes/6923/', '/senado/votacoes/100-1/', '/senado/votacoes/9999/', '/senado/votacoes/abc/', '/senado/votacoes/6923-1/']);

test('roll call shows its result', function (?bool $approved, string $label) {
    foreach (['camara' => '/votacoes/100-1/', 'senado' => '/senado/votacoes/6923/'] as $house => $path) {
        DB::table('roll_calls')->where('house', $house)->where('source_id', basename($path))->update(['approved' => $approved]);
        $result = html($this->get($path))->querySelector('.ma-result');
        expect(array_map(fn ($p) => textOf($p), iterator_to_array($result->querySelectorAll('.ma-t-title-1'))))->toBe([$label], $path);
    }
})->with([
    'approved' => [true, 'Aprovada'],
    'rejected' => [false, 'Rejeitada'],
    'not informed' => [null, 'Resultado não informado'],
]);

test('roll call shows the government orientation', function (?string $orientation, string $line) {
    foreach (['camara' => '/votacoes/100-1/', 'senado' => '/senado/votacoes/6923/'] as $house => $path) {
        DB::table('roll_calls')->where('house', $house)->where('source_id', basename($path))->update(['government_orientation' => $orientation]);
        $lines = array_map(fn ($p) => textOf($p), iterator_to_array(html($this->get($path))->querySelectorAll('.ma-result > p.ma-t-small')));
        expect($lines)->toBe([$line], $path);
    }
})->with([
    'yes' => ['yes', 'Orientação do governo: Sim'],
    'no' => ['no', 'Orientação do governo: Não'],
    'abstention' => ['abstention', 'Orientação do governo: Abstenção'],
    'obstruction' => ['obstruction', 'Orientação do governo: Obstrução'],
    'free' => ['free', 'Orientação do governo: Liberado'],
    'absent' => [null, 'Sem orientação do governo registrada'],
]);
