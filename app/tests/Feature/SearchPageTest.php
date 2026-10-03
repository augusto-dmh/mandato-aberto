<?php

use App\Support\SearchKey;
use Database\Seeders\BudgetSeeder;
use Dom\HTMLDocument;

// Checks C6-C22 of .specs/features/app-home/checks.md.

/** The C6 list: the in-exercise memberships of 58 in the search fixture, in their only order. */
const DEFAULT_ROWS = [
    ['Abel Nunes', '/deputados/107/'],
    ['Ágata Rocha', '/deputados/105/'],
    ['Ana Souza', '/deputados/101/'],
    ['João da Silva', '/deputados/104/'],
    ['Paulo Silva', '/deputados/108/'],
    ['Paulo Silva', '/deputados/1001/'],
    ['Paulo Silva', '/senadores/9107/'],
    ['Zélia Moura', '/senadores/9108/'],
];

/** @return list<array{string, string}> name and href of each result row */
function resultRows(HTMLDocument $doc): array
{
    return array_map(fn ($a) => [textOf($a->querySelector('.ma-result__name')), (string) $a->getAttribute('href')],
        iterator_to_array($doc->querySelectorAll('.ma-results li > a')));
}

function searchDoc(string $query = ''): HTMLDocument
{
    $response = test()->get('/busca/'.($query === '' ? '' : "?{$query}"));
    $response->assertOk();

    return html($response);
}

function countLine(HTMLDocument $doc): string
{
    return textOf($doc->querySelector('.ma-results__count'));
}

beforeEach(function () {
    requireSsr();
});

test('search lists the members in exercise by default', function () {
    importSearchFixture();

    $response = $this->get('/busca/');

    $response->assertOk();
    expect($response->viewData('page')['component'])->toBe('Search/Index');
    $doc = html($response);
    expect(countLine($doc))->toBe('8 parlamentares, em ordem alfabética')
        ->and(resultRows($doc))->toBe(DEFAULT_ROWS);
    expect(countLine(searchDoc('casa=senado&uf=SP')))->toBe('1 parlamentar, em ordem alfabética');
});

test('search rows link the member page of that legislature', function () {
    importSearchFixture();
    $row = fn (HTMLDocument $doc, string $href) => $doc->querySelector(".ma-results li > a[href=\"{$href}\"]");

    $doc = searchDoc();
    expect(textOf($row($doc, '/deputados/101/')))->toBe('Ana Souza Câmara dos Deputados PT · SP')
        ->and(textOf($row($doc, '/senadores/9107/')))->toBe('Paulo Silva Senado Federal PT · SP');
    foreach ($doc->querySelectorAll('.ma-results li') as $li) {
        expect($li->querySelectorAll('a'))->toHaveCount(1)
            ->and($li->querySelectorAll('img, .ma-num'))->toHaveCount(0)
            ->and(textOf($li))->not->toMatch('/\d/');
    }

    $past = searchDoc('legislatura=57');
    expect(textOf($row($past, '/deputados/101/legislatura/57/')))->toBe('Ana Souza Câmara dos Deputados PSB · SP')
        ->and($row($past, '/senadores/9101/legislatura/57/'))->not->toBeNull()
        ->and($past->querySelectorAll('.ma-results li img, .ma-results li .ma-num'))->toHaveCount(0);
});

test('search matches every token ignoring case and accents', function () {
    importSearchFixture();
    $joao = [['João da Silva', '/deputados/104/']];

    expect(resultRows(searchDoc('q=joao')))->toBe($joao)
        ->and(resultRows(searchDoc('q='.urlencode('JOÃO'))))->toBe($joao)
        ->and(resultRows(searchDoc('q=silva%20jo%C3%A3o')))->toBe($joao)
        ->and(resultRows(searchDoc('q=joao%20santos')))->toBe([])
        ->and(resultRows(searchDoc('q=silva')))->toBe([
            ['João da Silva', '/deputados/104/'],
            ['Paulo Silva', '/deputados/108/'],
            ['Paulo Silva', '/deputados/1001/'],
            ['Paulo Silva', '/senadores/9107/'],
        ]);
});

test('search treats wildcards as literal characters', function () {
    importSearchFixture();

    foreach (['q=%25', 'q=_', 'q=%5C'] as $query) {
        $doc = searchDoc($query);
        expect(resultRows($doc))->toBe([], $query)
            ->and(textOf($doc->querySelector('.ma-empty')))->toContain('Nenhum parlamentar encontrado com esses filtros.');
    }
});

test('search reads the first 100 characters of q', function () {
    importSearchFixture();
    $q = 'João'.str_repeat(' ', 96).'x';
    expect(mb_strlen($q))->toBe(101);

    $doc = searchDoc('q='.rawurlencode($q));

    expect(resultRows($doc))->toBe([['João da Silva', '/deputados/104/']])
        ->and($doc->querySelector('input[name="q"]')->getAttribute('value'))->toBe('João');
});

test('search filters by house uf and party combined', function () {
    importSearchFixture();

    expect(array_column(resultRows(searchDoc('casa=camara')), 1))
        ->toBe(['/deputados/107/', '/deputados/105/', '/deputados/101/', '/deputados/104/', '/deputados/108/', '/deputados/1001/'])
        ->and(array_column(resultRows(searchDoc('uf=SP')), 1))
        ->toBe(['/deputados/101/', '/deputados/104/', '/deputados/108/', '/senadores/9107/'])
        ->and(array_column(resultRows(searchDoc('partido=PL')), 1))
        ->toBe(['/deputados/108/', '/deputados/1001/'])
        ->and(array_column(resultRows(searchDoc('casa=camara&uf=SP&partido=PT')), 1))
        ->toBe(['/deputados/101/', '/deputados/104/']);
});

test('search lists only members in exercise unless asked for all', function () {
    importSearchFixture();

    expect(resultRows(searchDoc('situacao=todos')))->toBe([
        ['Abel Nunes', '/deputados/107/'],
        ['Ágata Rocha', '/deputados/105/'],
        ['Ana Souza', '/deputados/101/'],
        ['João da Silva', '/deputados/104/'],
        ['Mariana Silva', '/deputados/106/'],
        ['Otávio Brandão', '/senadores/9109/'],
        ['Paulo Silva', '/deputados/108/'],
        ['Paulo Silva', '/deputados/1001/'],
        ['Paulo Silva', '/senadores/9107/'],
        ['Zélia Moura', '/senadores/9108/'],
    ])->and(resultRows(searchDoc('situacao=exercicio')))->toBe(DEFAULT_ROWS)
        ->and(array_column(resultRows(searchDoc()), 0))->not->toContain('Otávio Brandão');
});

test('search of a past legislature lists everyone who held a mandate', function () {
    importSearchFixture();
    $names = ['Ana Souza', 'Bruno Lima', 'Carla Dias', 'Rosa Andrade', 'Sérgio Prado', 'Teresa Lins', 'Ubiratan Costa', 'Vera Dantas', 'Wagner Reis'];
    $note = 'A 57ª legislatura terminou em 31/01/2027; a lista mostra todos que tiveram mandato nela.';

    foreach (['legislatura=57', 'legislatura=57&situacao=exercicio'] as $query) {
        $doc = searchDoc($query);
        expect(array_column(resultRows($doc), 0))->toBe($names, $query)
            ->and(textOf($doc->querySelector('.ma-results__note')))->toBe($note);
    }
    expect(searchDoc()->querySelector('.ma-results__note'))->toBeNull()
        ->and(textOf(searchDoc()->getElementById('app')))->not->toContain('terminou em');
});

test('search ignores a filter value outside its set', function () {
    importSearchFixture();
    $selected = fn (HTMLDocument $doc, string $name) => textOf($doc->querySelector("select[name=\"{$name}\"] option[selected]"));
    $cases = [
        'casa=foo' => ['casa', 'Todas as Casas'],
        'uf=XX' => ['uf', 'Todas as UFs'],
        'uf=sp' => ['uf', 'Todas as UFs'],
        'partido=XYZ' => ['partido', 'Todos os partidos'],
        'partido=PSB' => ['partido', 'Todos os partidos'],
        'legislatura=56' => ['legislatura', '58ª legislatura (2027–2031)'],
        'legislatura=abc' => ['legislatura', '58ª legislatura (2027–2031)'],
        'situacao=foo' => ['situacao', 'Em exercício'],
    ];
    foreach ($cases as $query => [$field, $default]) {
        $doc = searchDoc($query);
        expect(resultRows($doc))->toBe(DEFAULT_ROWS, $query)
            ->and(countLine($doc))->toBe('8 parlamentares, em ordem alfabética')
            ->and($selected($doc, $field))->toBe($default, $query);
    }

    expect(resultRows(searchDoc('legislatura=57&partido=PSB')))->toBe([
        ['Ana Souza', '/deputados/101/legislatura/57/'],
        ['Wagner Reis', '/senadores/9106/legislatura/57/'],
    ]);
});

test('search orders by name then house then id only', function () {
    importSearchFixture();

    $doc = searchDoc();
    $names = array_column(resultRows($doc), 0);
    $sortedBytes = $names;
    sort($sortedBytes, SORT_STRING);
    expect($names[1])->toBe('Ágata Rocha')
        ->and(end($sortedBytes))->toBe('Ágata Rocha')
        ->and(array_slice(array_column(resultRows($doc), 1), 4, 3))->toBe(['/deputados/108/', '/deputados/1001/', '/senadores/9107/']);
    expect(resultRows(searchDoc('ordem=nome-desc')))->toBe(DEFAULT_ROWS)
        ->and(resultRows(searchDoc('sort=uf')))->toBe(DEFAULT_ROWS);

    $controls = array_map(fn ($c) => (string) $c->getAttribute('name'), iterator_to_array($doc->getElementById('app')->querySelectorAll('input, select, button')));
    expect($controls)->toBe(['q', 'casa', 'uf', 'partido', 'legislatura', 'situacao', '']);
});

test('search pages by 50 keeping the other parameters', function () {
    $this->seed(BudgetSeeder::class);
    $pager = fn (HTMLDocument $doc) => [
        'text' => textOf($doc->querySelector('.ma-pages__current')),
        'prev' => $doc->querySelector('.ma-pages a[rel="prev"]')?->getAttribute('href'),
        'prevText' => textOf($doc->querySelector('.ma-pages a[rel="prev"]')),
        'next' => $doc->querySelector('.ma-pages a[rel="next"]')?->getAttribute('href'),
        'nextText' => textOf($doc->querySelector('.ma-pages a[rel="next"]')),
    ];

    $first = searchDoc();
    expect(resultRows($first))->toHaveCount(50)
        ->and($pager($first))->toBe(['text' => 'Página 1 de 14', 'prev' => null, 'prevText' => '', 'next' => '/busca/?pagina=2', 'nextText' => 'Próxima página']);

    $second = searchDoc('casa=camara&pagina=2');
    expect(resultRows($second))->toHaveCount(50)
        ->and($pager($second))->toBe(['text' => 'Página 2 de 12', 'prev' => '/busca/?casa=camara', 'prevText' => 'Página anterior', 'next' => '/busca/?casa=camara&pagina=3', 'nextText' => 'Próxima página']);

    $last = searchDoc('casa=camara&pagina=12');
    expect(resultRows($last))->toHaveCount(50)
        ->and($pager($last))->toBe(['text' => 'Página 12 de 12', 'prev' => '/busca/?casa=camara&pagina=11', 'prevText' => 'Página anterior', 'next' => null, 'nextText' => '']);

    expect(resultRows(searchDoc('casa=senado&pagina=2')))->toHaveCount(31);

    $kept = searchDoc('situacao=todos&q=a&legislatura=58&pagina=2');
    expect($pager($kept)['prev'])->toBe('/busca/?q=a&legislatura=58&situacao=todos');

    $collator = new Collator('pt_BR');
    $all = [];
    $previousLast = null;
    for ($p = 1; $p <= 12; $p++) {
        $names = array_column(resultRows(searchDoc("casa=camara&pagina={$p}")), 0);
        $hrefs = array_column(resultRows(searchDoc("casa=camara&pagina={$p}")), 1);
        if ($previousLast !== null) {
            expect($collator->compare($previousLast, $names[0]))->toBeLessThanOrEqual(0, "page {$p}");
        }
        $previousLast = end($names);
        $all = [...$all, ...$hrefs];
    }
    expect(array_unique($all))->toHaveCount(600);
});

test('search pages by 50 keeping the other parameters, no pager on one page', function () {
    importSearchFixture();
    $doc = searchDoc();

    expect($doc->querySelector('.ma-pages'))->toBeNull()
        ->and(textOf($doc->getElementById('app')))->not->toContain('Página');
});

test('search answers 404 for a page out of range', function () {
    importSearchFixture();

    foreach (['99', '0', '-1', 'abc', '1.5', '2'] as $page) {
        $response = $this->get("/busca/?pagina={$page}");
        $response->assertNotFound();
        expect(textOf(html($response)->querySelector('h1')))->toBe('Página não encontrada', $page);
    }
    $this->get('/busca/?pagina=1')->assertOk();
    $empty = $this->get('/busca/?q=zzz&pagina=99');
    $empty->assertOk();
    expect(textOf(html($empty)->querySelector('.ma-empty')))->toContain('Nenhum parlamentar encontrado com esses filtros.');
});

test('search with no match offers to clear the filters', function () {
    importSearchFixture();
    $doc = searchDoc('q=zzz');

    $empty = $doc->querySelector('.ma-empty');
    $clear = $empty?->querySelector('a');
    expect(textOf($empty))->toBe('Nenhum parlamentar encontrado com esses filtros. Limpar filtros')
        ->and(textOf($clear))->toBe('Limpar filtros')
        ->and($clear->getAttribute('href'))->toBe('/busca/')
        ->and($doc->querySelector('.ma-results'))->toBeNull()
        ->and($doc->querySelector('.ma-results__count'))->toBeNull();
});

test('search without data says so', function () {
    foreach (['/busca/', '/busca/?q=ana'] as $path) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        expect(textOf($doc->getElementById('app')))->toContain('Ainda não há dados importados.')
            ->and($doc->querySelectorAll('form'))->toHaveCount(0);
    }
});

test('search echoes q only in its input', function () {
    importSearchFixture();
    $selected = fn (HTMLDocument $doc, string $name) => textOf($doc->querySelector("select[name=\"{$name}\"] option[selected]"));

    $doc = searchDoc('q=joao&casa=camara&uf=SP&partido=PT&situacao=todos');
    expect($doc->querySelector('input[name="q"]')->getAttribute('value'))->toBe('joao')
        ->and($selected($doc, 'casa'))->toBe('Câmara dos Deputados')
        ->and($selected($doc, 'uf'))->toBe('SP')
        ->and($selected($doc, 'partido'))->toBe('PT')
        ->and($selected($doc, 'legislatura'))->toBe('58ª legislatura (2027–2031)')
        ->and($selected($doc, 'situacao'))->toBe('Todos');

    foreach (['joao' => 'q=joao&casa=camara&uf=SP&partido=PT&situacao=todos', 'zzqx' => 'q=zzqx'] as $q => $query) {
        $response = $this->get("/busca/?{$query}");
        $doc = html($response);
        foreach ($doc->querySelectorAll('script[data-page]') as $script) {
            $script->remove();
        }
        $markup = $doc->saveHtml();
        expect(substr_count($markup, $q))->toBe(1, $q)
            ->and($doc->querySelector('input[name="q"]')->getAttribute('value'))->toBe($q)
            ->and(textOf($doc->querySelector('title')))->not->toContain($q)
            ->and(implode(' ', textsOf($doc->documentElement, 'h1, h2, h3')))->not->toContain($q)
            ->and(textOf($doc->querySelector('.ma-results__count')))->not->toContain($q)
            ->and(textOf($doc->querySelector('.ma-empty')))->not->toContain($q);
        foreach ($doc->querySelectorAll('meta') as $meta) {
            expect((string) $meta->getAttribute('content'))->not->toContain($q);
        }
        expect($response->viewData('page')['props']['meta'])->not->toContain($q);
    }
});

test('search head tags keep the query out', function () {
    importSearchFixture();
    $doc = html($this->get('/busca/?q=ana&uf=SP'));
    $tags = headTags($doc);
    $description = 'Busque deputados federais e senadores por nome, Casa, UF, partido e legislatura, em ordem alfabética.';

    expect($tags['title'])->toBe(['Buscar parlamentares - Mandato Aberto'])
        ->and($tags['og:title'])->toBe(['Buscar parlamentares'])
        ->and($tags['description'])->toBe([$description])
        ->and($tags['og:description'])->toBe([$description])
        ->and($tags['canonical'])->toBe(['https://mandato.test/busca/'])
        ->and($tags['og:url'])->toBe(['https://mandato.test/busca/'])
        ->and(array_map(fn ($m) => $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll('meta[name="robots"]'))))
        ->toBe(['noindex, follow']);
});

test('search key folds case accents and spaces', function () {
    expect(SearchKey::of("  JOÃO   da\tSilva "))->toBe('joao da silva')
        ->and(SearchKey::of('Ágata Çé Ü'))->toBe('agata ce u')
        ->and(SearchKey::of('100%_x\\'))->toBe('100%_x\\')
        ->and(SearchKey::of(''))->toBe('');
});
