<?php

use Dom\Element;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Checks C62-C66 of .specs/features/app-contract-v3/checks.md.

beforeEach(function () {
    requireSsr();
});

/** Copy of the plan's methodology table (AC 49), by section id. */
const METHOD_COPY = [
    'bases' => 'Cada indicador aparece em duas bases. "Todas as votações nominais do plenário" conta toda votação nominal ou secreta do plenário. "Votações sobre propostas e emendas" conta só as classificadas como decisão sobre a proposta ou como emenda, destaque ou parte do texto, pelas regras publicadas nesta página. Nenhuma pessoa escolhe votação por votação.',
    'participacao' => 'Base: votações nominais e secretas do plenário na legislatura, realizadas enquanto o parlamentar estava em exercício. Conta: aquelas com voto registrado, inclusive a presidência da sessão e o voto em votação secreta. Licenças, missões e registros sem voto entram na base e não na conta.',
    'alinhamento-governo' => 'Base: votos Sim, Não, Abstenção ou Obstrução do parlamentar em votações do plenário em que o governo orientou Sim, Não, Abstenção ou Obstrução. Conta: votos iguais à orientação do governo. Votações secretas e orientações "Liberado" ficam fora da base.',
    'alinhamento-partido' => 'Base: votos Sim, Não, Abstenção ou Obstrução do parlamentar em votações do plenário em que a maioria dos demais membros do mesmo partido votou uma dessas opções, sem empate. Conta: votos iguais a essa maioria.',
    'votacoes-simbolicas' => 'Na votação simbólica, o plenário decide sem registrar o voto de cada parlamentar. A página do parlamentar informa quantas votações simbólicas sobre propostas e emendas ocorreram durante o exercício; esse número não entra em nenhum indicador. O Senado Federal não publica votações simbólicas como registros de votação.',
    'proposicoes' => 'Contamos as proposições apresentadas dentro da legislatura. Câmara: PL, PLP, PEC, PDL e PRC como autoria; REQ, RIC e INC como requerimentos. Senado: PL, PLP, PEC, PDL e PRS como autoria; RQS, REQ e INS como requerimentos.',
    'tipos-de-votacao' => 'Nominal: cada voto fica registrado com o nome do parlamentar. Secreta: a Casa registra quem votou, não como votou. Simbólica: o resultado é proclamado sem registro individual.',
    'classificacao' => 'As regras são aplicadas na ordem da tabela ao texto oficial da votação, sem acentos, em minúsculas e com espaços simples; vale a primeira que corresponder. Uma votação sem regra correspondente entra em "todas as votações nominais do plenário" e fica fora da base de propostas e emendas.',
    'registros-sem-voto' => 'O Senado publica o tipo de licença de cada senador; aqui todas as licenças aparecem como "Licença".',
    'cobertura' => 'Quantas votações cada importação trouxe, por Casa e legislatura.',
];

/** @return list<list<string>> the cell texts of each body row of the tables under `$root` */
function tableRows(Element $root): array
{
    return array_map(fn ($tr) => array_map(fn ($td) => textOf($td), iterator_to_array($tr->querySelectorAll('td'))), iterator_to_array($root->querySelectorAll('tbody tr')));
}

test('methodology page has every section in order', function () {
    importFixtures();
    $response = $this->get('/metodologia/');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('Methodology/Show'));
    $doc = html($response);
    expect(array_map(fn ($h) => textOf($h), iterator_to_array($doc->querySelectorAll('h1'))))->toBe(['Metodologia']);
    $sections = iterator_to_array($doc->querySelectorAll('section[id]'));
    expect(array_map(fn ($s) => $s->getAttribute('id'), $sections))->toBe(array_keys(METHOD_COPY));
    foreach ($sections as $section) {
        $paragraphs = array_map(fn ($p) => textOf($p), iterator_to_array($section->querySelectorAll('p')));
        expect($paragraphs)->toContain(METHOD_COPY[$section->getAttribute('id')]);
    }

    $records = $doc->getElementById('registros-sem-voto');
    expect(array_map(fn ($th) => textOf($th), iterator_to_array($records->querySelectorAll('thead th'))))->toBe(['Casa', 'Registro oficial', 'Como aparece aqui'])
        ->and(tableRows($records))->toBe([
            ['Câmara dos Deputados', 'Sim', 'Sim'],
            ['Câmara dos Deputados', 'Não', 'Não'],
            ['Câmara dos Deputados', 'Abstenção', 'Abstenção'],
            ['Câmara dos Deputados', 'Obstrução', 'Obstrução'],
            ['Câmara dos Deputados', 'Artigo 17', 'Art. 17 (presidente da sessão)'],
            ['Câmara dos Deputados', '(vazio, em votação secreta)', 'Votação secreta'],
            ['Câmara dos Deputados', '(vazio)', 'Registro sem voto'],
            ['Senado Federal', 'Presidente (art. 51 RISF)', 'Presidente da sessão (art. 51 RISF)'],
            ['Senado Federal', 'Votou', 'Votou (votação secreta)'],
            ['Senado Federal', 'P-NRV', 'Sem voto: Presente, não registrou voto'],
            ['Senado Federal', 'AP', 'Sem voto: Atividade parlamentar'],
            ['Senado Federal', 'MIS', 'Sem voto: Missão da Casa no País ou no exterior'],
            ['Senado Federal', 'NCom', 'Sem voto: Não compareceu'],
            ['Senado Federal', 'NA', 'Sem voto: Dispositivo não citado'],
            ['Senado Federal', 'Licença', 'Sem voto: Licença'],
        ]);
    $after = $records->querySelector('.ma-table')->nextElementSibling;
    expect(textOf($after))->toBe(METHOD_COPY['registros-sem-voto']);
});

test("methodology lists each house's rules in stored order", function () {
    importFixtures();
    $section = html($this->get('/metodologia/'))->getElementById('classificacao');
    $kinds = ['final' => 'Decisão sobre a proposta', 'amendment' => 'Emenda, destaque ou parte do texto', 'procedural' => 'Procedimento'];

    expect(array_map(fn ($h) => textOf($h), iterator_to_array($section->querySelectorAll('h3'))))
        ->toBe(['Regras da Câmara dos Deputados, versão 1', 'Regras do Senado Federal, versão 2']);
    [$camara, $senado] = iterator_to_array($section->querySelectorAll('table'));
    expect(array_map(fn ($th) => textOf($th), iterator_to_array($camara->querySelectorAll('thead th'))))->toBe(['Regra', 'Tipo', 'Campo', 'Padrão', 'Descrição']);

    foreach ([[$camara, 'camara', 11], [$senado, 'senado', 2]] as [$table, $house, $count]) {
        $rules = readJson(fixtureDir($house).'/classification-rules.json');
        $rows = iterator_to_array($table->querySelectorAll('tbody tr'));
        expect($rows)->toHaveCount($count)
            ->and(array_map(fn ($tr) => $tr->getAttribute('id'), $rows))->toBe(array_map(fn ($i) => sprintf("regra-{$house}-%02d", $i), range(1, $count)));
        foreach ($rules as $i => $r) {
            expect(tableRows($table)[$i])->toBe([$r['id'], $kinds[$r['kind']], $r['field'], $r['pattern'], $r['description']])
                ->and(textOf($rows[$i]->querySelectorAll('td')->item(3)->querySelector('code')))->toBe($r['pattern']);
        }
    }
});

test('methodology says when a house has no import', function () {
    runImport(['dir' => fixtureDir('camara')]);
    $doc = html($this->get('/metodologia/'));
    foreach (['classificacao', 'cobertura'] as $id) {
        $section = $doc->getElementById($id);
        expect(textOf($section))->toContain('Ainda não há dados importados do Senado Federal.')
            ->and(textOf($section))->not->toContain('Ainda não há dados importados da Câmara dos Deputados.')
            ->and(textOf($section))->not->toContain('Senado Federal, versão')
            ->and(collect(tableRows($section))->filter(fn ($r) => in_array('Senado Federal', $r, true) || str_starts_with($r[0] ?? '', 'senado.'))->all())->toBe([]);
    }

    foreach (['votes', 'authorships', 'exercise_periods', 'full_texts', 'memberships', 'roll_calls', 'classification_rules', 'propositions', 'members', 'contract_imports', 'legislatures'] as $table) {
        DB::table($table)->delete();
    }
    $empty = html($this->get('/metodologia/'));
    foreach (['classificacao', 'cobertura'] as $id) {
        $section = $empty->getElementById($id);
        expect(textOf($section))->toContain('Ainda não há dados importados do Senado Federal.')
            ->and(textOf($section))->toContain('Ainda não há dados importados da Câmara dos Deputados.')
            ->and($section->querySelectorAll('table'))->toHaveCount(0);
    }
});

test('methodology shows coverage per house and legislature', function () {
    importFixtures();
    $section = html($this->get('/metodologia/'))->getElementById('cobertura');

    expect(textOf($section->querySelector('p')))->toBe('Quantas votações cada importação trouxe, por Casa e legislatura.')
        ->and(array_map(fn ($th) => textOf($th), iterator_to_array($section->querySelector('table')->querySelectorAll('thead th'))))
        ->toBe(['Casa', 'Legislatura', 'Dados até', 'Nominais', 'Secretas', 'Simbólicas', 'Sem regra correspondente'])
        ->and(tableRows($section))->toBe([
            ['Câmara dos Deputados', '57ª', '01/09/2025', '4', '2', '1', '1'],
            ['Câmara dos Deputados', '58ª', '15/02/2027', '1', '0', '0', '0'],
            ['Senado Federal', '57ª', '10/06/2025', '1', '1', 'não publicadas pela Casa', '1'],
        ]);
});

test('methodology head and every method link point to the app', function () {
    importFixtures();
    $tags = headTags(html($this->get('/metodologia/')));
    expect($tags['title'])->toBe(['Metodologia - Mandato Aberto'])
        ->and($tags['canonical'])->toBe(['https://mandato.test/metodologia/'])
        ->and($tags['og:url'])->toBe(['https://mandato.test/metodologia/']);

    foreach (RENDERED_PAGES as $path) {
        $response = $this->get($path);
        $response->assertOk();
        expect((string) $response->getContent())->not->toContain('augusto-dmh.github.io/mandato-aberto/metodologia');
        if (str_starts_with($path, '/deputados/') || str_starts_with($path, '/senadores/')) {
            $links = array_values(array_filter(
                array_map(fn ($a) => (string) $a->getAttribute('href'), iterator_to_array(html($response)->querySelectorAll('.ma-note a'))),
                fn ($href) => str_contains($href, 'metodologia'),
            ));
            expect(count($links))->toBe(8, $path);
            foreach ($links as $href) {
                expect($href)->toStartWith('https://mandato.test/metodologia/#');
            }
        }
    }
});
