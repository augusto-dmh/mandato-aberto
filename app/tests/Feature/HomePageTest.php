<?php

use Dom\HTMLDocument;
use Dom\Node;

// Checks C1-C5 of .specs/features/app-home/checks.md.

const HOME_LEDE = 'O Mandato Aberto mostra como cada parlamentar votou e o que apresentou na Câmara dos Deputados e no Senado Federal, com dados abertos das duas Casas e a fonte e o método de cada número.';

const UF_CODES = ['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'];

/** @return list<array{value: string, label: string, selected: bool}> */
function selectOptions(HTMLDocument $doc, string $name): array
{
    $select = $doc->querySelector("form[role=\"search\"] select[name=\"{$name}\"]");
    expect($select)->not->toBeNull($name);

    return array_map(fn ($o) => [
        'value' => (string) $o->getAttribute('value'),
        'label' => textOf($o),
        'selected' => $o->hasAttribute('selected'),
    ], iterator_to_array($select->querySelectorAll('option')));
}

function labelOf(HTMLDocument $doc, string $name): string
{
    $control = $doc->querySelector("form[role=\"search\"] [name=\"{$name}\"]");

    return textOf($doc->querySelector('label[for="'.$control?->getAttribute('id').'"]'));
}

test('home says what the site is', function () {
    requireSsr();
    importFixtures();

    $response = $this->get('/');

    $response->assertOk();
    expect($response->viewData('page')['component'])->toBe('Home/Index');
    $doc = html($response);
    $h1 = $doc->querySelectorAll('h1');
    expect($h1)->toHaveCount(1)
        ->and(textOf($h1->item(0)))->toBe('O que cada parlamentar federal fez no mandato')
        ->and(textOf($h1->item(0)->nextElementSibling))->toBe(HOME_LEDE);
});

test('home search form works without javascript', function () {
    requireSsr();
    importFixtures();
    $doc = html($this->get('/'));

    $forms = $doc->querySelectorAll('form');
    expect($forms)->toHaveCount(1);
    $form = $forms->item(0);
    expect($form->getAttribute('role'))->toBe('search')
        ->and($form->getAttribute('method'))->toBe('get')
        ->and($form->getAttribute('action'))->toBe('/busca/');

    $controls = array_map(fn ($c) => $c->tagName.':'.$c->getAttribute('name'), iterator_to_array($form->querySelectorAll('input, select, button')));
    expect($controls)->toBe(['INPUT:q', 'SELECT:casa', 'SELECT:uf', 'SELECT:partido', 'SELECT:legislatura', 'SELECT:situacao', 'BUTTON:']);
    expect($form->querySelector('input[name="q"]')->getAttribute('type'))->toBe('text')
        ->and(textOf($form->querySelector('button')))->toBe('Buscar')
        ->and($form->querySelector('button')->getAttribute('type'))->toBe('submit');
    foreach (['q' => 'Nome', 'casa' => 'Casa', 'uf' => 'UF', 'partido' => 'Partido', 'legislatura' => 'Legislatura', 'situacao' => 'Situação'] as $name => $label) {
        expect(labelOf($doc, $name))->toBe($label, $name);
    }

    expect(selectOptions($doc, 'casa'))->toBe([
        ['value' => '', 'label' => 'Todas as Casas', 'selected' => true],
        ['value' => 'camara', 'label' => 'Câmara dos Deputados', 'selected' => false],
        ['value' => 'senado', 'label' => 'Senado Federal', 'selected' => false],
    ]);
    expect(selectOptions($doc, 'uf'))->toBe([
        ['value' => '', 'label' => 'Todas as UFs', 'selected' => true],
        ...array_map(fn ($uf) => ['value' => $uf, 'label' => $uf, 'selected' => false], UF_CODES),
    ]);
    expect(selectOptions($doc, 'partido'))->toBe([
        ['value' => '', 'label' => 'Todos os partidos', 'selected' => true],
        ['value' => 'PT', 'label' => 'PT', 'selected' => false],
    ]);
    expect(selectOptions($doc, 'legislatura'))->toBe([
        ['value' => '58', 'label' => '58ª legislatura (2027–2031)', 'selected' => true],
        ['value' => '57', 'label' => '57ª legislatura (2023–2027)', 'selected' => false],
    ]);
    expect(selectOptions($doc, 'situacao'))->toBe([
        ['value' => 'exercicio', 'label' => 'Em exercício', 'selected' => true],
        ['value' => 'todos', 'label' => 'Todos', 'selected' => false],
    ]);

    importSearchFixture(); // a later snapshot of both houses, adding the search members
    expect(array_column(selectOptions(html($this->get('/')), 'partido'), 'label'))
        ->toBe(['Todos os partidos', 'MDB', 'PL', 'PP', 'PSD', 'PSOL', 'PT']);
});

/**
 * The house blocks of a page: heading, the `.ma-count` lines and each note's text and links.
 *
 * @return list<array{heading: string, counts: list<string>, notes: list<array{text: string, links: list<array{string, string}>}>}>
 */
function houseBlocks(HTMLDocument $doc): array
{
    return array_map(fn ($s) => [
        'heading' => textOf($s->querySelector('h2')),
        'counts' => textsOf($s, '.ma-count'),
        'notes' => array_map(fn ($n) => [
            'text' => textOf($n),
            'links' => array_map(fn ($a) => [textOf($a), (string) $a->getAttribute('href')], iterator_to_array($n->querySelectorAll('a'))),
        ], iterator_to_array($s->querySelectorAll('.ma-note'))),
    ], iterator_to_array($doc->querySelectorAll('.ma-house')));
}

test('home counts the current legislature per house', function () {
    requireSsr();
    importFixtures();
    $doc = html($this->get('/'));

    $camaraNote = fn (string $anchor) => [
        'text' => 'Fonte: Câmara dos Deputados, dados de 01/03/2027 · Como calculamos',
        'links' => [['Câmara dos Deputados', 'https://dadosabertos.camara.leg.br/'], ['Como calculamos', "https://mandato.test/metodologia/#{$anchor}"]],
    ];
    expect(houseBlocks($doc))->toBe([[
        'heading' => 'Câmara dos Deputados',
        'counts' => ['1 votação nominal no plenário', '1 parlamentar com mandato na legislatura'],
        'notes' => [$camaraNote('tipos-de-votacao'), $camaraNote('cobertura')],
    ]]);
    $overview = $doc->querySelectorAll('a[href="/legislaturas/58/"]');
    expect($overview)->toHaveCount(1)
        ->and(textOf($overview->item(0)))->toBe('Visão geral da 58ª legislatura');
    $blocks = $doc->querySelectorAll('.ma-house');
    $last = $blocks->item($blocks->length - 1);
    expect($last->compareDocumentPosition($overview->item(0)) & Node::DOCUMENT_POSITION_FOLLOWING)->toBeGreaterThan(0)
        ->and($last->contains($overview->item(0)))->toBeFalse();

    importSearchFixture(); // a later snapshot of both houses, adding the search members
    $senateNote = fn (string $anchor) => [
        'text' => 'Fonte: Senado Federal, dados de 05/03/2027 · Como calculamos',
        'links' => [['Senado Federal', 'https://legis.senado.leg.br/dadosabertos/'], ['Como calculamos', "https://mandato.test/metodologia/#{$anchor}"]],
    ];
    expect(houseBlocks(html($this->get('/'))))->toBe([
        [
            'heading' => 'Câmara dos Deputados',
            'counts' => ['1 votação nominal no plenário', '7 parlamentares com mandato na legislatura'],
            'notes' => [$camaraNote('tipos-de-votacao'), $camaraNote('cobertura')],
        ],
        [
            'heading' => 'Senado Federal',
            'counts' => ['0 votações nominais no plenário', '3 parlamentares com mandato na legislatura'],
            'notes' => [$senateNote('tipos-de-votacao'), $senateNote('cobertura')],
        ],
    ]);
});

test('home without data says so', function () {
    requireSsr();

    $response = $this->get('/');

    $response->assertOk();
    $doc = html($response);
    expect(textOf($doc->querySelector('h1')))->toBe('O que cada parlamentar federal fez no mandato')
        ->and(textOf($doc->querySelector('h1')->nextElementSibling))->toBe(HOME_LEDE)
        ->and(textOf($doc->getElementById('app')))->toContain('Ainda não há dados importados.')
        ->and($doc->querySelectorAll('form'))->toHaveCount(0)
        ->and($doc->querySelectorAll('.ma-house'))->toHaveCount(0)
        ->and($doc->querySelectorAll('a[href^="/legislaturas/"]'))->toHaveCount(0);
});

test('home head tags', function () {
    importFixtures();
    $doc = html($this->get('/'));
    $tags = headTags($doc);

    expect($tags['title'])->toBe(['O que cada parlamentar federal fez no mandato - Mandato Aberto'])
        ->and($tags['og:title'])->toBe(['O que cada parlamentar federal fez no mandato'])
        ->and($tags['description'])->toBe([HOME_LEDE])
        ->and($tags['og:description'])->toBe([HOME_LEDE])
        ->and($tags['canonical'])->toBe(['https://mandato.test/'])
        ->and($tags['og:url'])->toBe(['https://mandato.test/'])
        ->and($doc->querySelectorAll('meta[name="robots"]'))->toHaveCount(0);
});
