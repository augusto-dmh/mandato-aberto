<?php

use App\Models\ContractImport;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Checks C34-C49, C74, C78 and C82 of .specs/features/app-contract-v3/checks.md.

beforeEach(function () {
    requireSsr();
    importFixtures();
});

/** The texts of the elements matching `$selector`, in document order. */
function texts(HTMLDocument|Element $root, string $selector): array
{
    return array_map(fn ($e) => textOf($e), iterator_to_array($root->querySelectorAll($selector)));
}

function attrs(HTMLDocument|Element $root, string $selector, string $attribute): array
{
    return array_map(fn ($e) => $e->getAttribute($attribute), iterator_to_array($root->querySelectorAll($selector)));
}

const BASES_PARAGRAPH = 'Votações sobre propostas e emendas são as que decidem um projeto, uma proposta de emenda à Constituição, uma medida provisória, uma emenda ou um destaque. As demais votações nominais tratam de procedimento, como requerimentos e recursos. A classificação segue regras publicadas.';

test('deputy page shows the latest mandate and each legislature', function () {
    $response = $this->get('/deputados/101/');
    $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('Members/Show'));
    $doc = html($response);
    expect(texts($doc, 'h1'))->toBe(['Ana Souza'])
        ->and(textOf($doc->querySelector('.ma-hero .ma-eyebrow')))->toBe('Câmara dos Deputados · 58ª legislatura · PT · SP');

    $earlier = $this->get('/deputados/101/legislatura/57/');
    $earlier->assertOk()->assertInertia(fn (Assert $page) => $page->component('Members/Show'));
    expect(textOf(html($earlier)->querySelector('.ma-hero .ma-eyebrow')))->toBe('Câmara dos Deputados · 57ª legislatura · PSB · SP');

    $latest = $this->get('/deputados/101/legislatura/58/');
    $latest->assertOk();
    expect(textOf(html($latest)->querySelector('.ma-hero .ma-eyebrow')))->toBe('Câmara dos Deputados · 58ª legislatura · PT · SP');
});

test('senator page shows the mandate', function () {
    foreach (['/senadores/9101/', '/senadores/9101/legislatura/57/'] as $path) {
        $response = $this->get($path);
        $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('Members/Show'));
        $doc = html($response);
        expect(texts($doc, 'h1'))->toBe(['Rosa Andrade'])
            ->and(textOf($doc->querySelector('.ma-hero .ma-eyebrow')))->toBe('Senado Federal · 57ª legislatura · PT · SP');
    }
});

test('member with two mandates lists the legislatures', function () {
    $nav = html($this->get('/deputados/101/'))->querySelector('nav[aria-label="Legislaturas"]');
    expect($nav)->not->toBeNull()
        ->and(texts($nav, 'a'))->toBe(['58ª legislatura (2027–2031)', '57ª legislatura (2023–2027)'])
        ->and(attrs($nav, 'a', 'href'))->toBe(['/deputados/101/legislatura/58/', '/deputados/101/legislatura/57/'])
        ->and(attrs($nav, 'a', 'aria-current'))->toBe(['page', null]);

    $earlier = html($this->get('/deputados/101/legislatura/57/'))->querySelector('nav[aria-label="Legislaturas"]');
    expect(attrs($earlier, 'a', 'aria-current'))->toBe([null, 'page']);
});

test('member with one mandate names its legislature', function () {
    foreach (['/deputados/102/', '/senadores/9101/'] as $path) {
        $doc = html($this->get($path));
        expect($doc->querySelector('nav[aria-label="Legislaturas"]'))->toBeNull()
            ->and(textOf($doc->querySelector('.ma-hero')))->toContain('57ª legislatura (2023–2027)');
    }
});

test('member head carries the share tags', function () {
    $description = 'Votos, participação em votações nominais e proposições de Ana Souza (PSB-SP) na Câmara dos Deputados, com dados oficiais e a base de cada número.';
    $tags = headTags(html($this->get('/deputados/101/legislatura/57/')));
    expect($tags['title'])->toBe(['Ana Souza (PSB-SP) na 57ª legislatura - Mandato Aberto'])
        ->and($tags['og:title'])->toBe(['Ana Souza (PSB-SP) na 57ª legislatura'])
        ->and($tags['description'])->toBe([$description])
        ->and($tags['og:description'])->toBe([$description])
        ->and($tags['og:type'])->toBe(['website'])
        ->and($tags['og:site_name'])->toBe(['Mandato Aberto'])
        ->and($tags['og:locale'])->toBe(['pt_BR'])
        ->and($tags['twitter:card'])->toBe(['summary']);

    $senator = headTags(html($this->get('/senadores/9101/')));
    expect($senator['title'])->toBe(['Rosa Andrade (PT-SP) na 57ª legislatura - Mandato Aberto'])
        ->and($senator['description'])->toBe(['Votos, participação em votações nominais e proposições de Rosa Andrade (PT-SP) no Senado Federal, com dados oficiais e a base de cada número.'])
        ->and($senator['og:description'])->toBe($senator['description']);
});

test('member canonical is the bare path for the latest mandate', function () {
    $cases = [
        '/deputados/101/' => '/deputados/101/',
        '/deputados/101/legislatura/58/' => '/deputados/101/',
        '/deputados/101/legislatura/57/' => '/deputados/101/legislatura/57/',
        '/senadores/9101/' => '/senadores/9101/',
        '/senadores/9101/legislatura/57/' => '/senadores/9101/',
    ];
    foreach ($cases as $path => $canonical) {
        $tags = headTags(html($this->get($path)));
        expect($tags['canonical'])->toBe(["https://mandato.test{$canonical}"], $path)
            ->and($tags['og:url'])->toBe(["https://mandato.test{$canonical}"], $path);
    }
});

test('member pages 404', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
    $doc = html($response);
    expect($doc->documentElement->getAttribute('lang'))->toBe('pt-BR')
        ->and(texts($doc, 'h1'))->toBe(['Página não encontrada']);
})->with([
    '/deputados/abc/', '/deputados/999999/', '/deputados/9101/', '/deputados/102/legislatura/58/', '/deputados/101/legislatura/56/',
    '/deputados/101/legislatura/abc/', '/senadores/101/', '/senadores/9101/legislatura/58/', '/senadores/abc/',
]);

test('partitura draws each plenary vote by position', function () {
    $doc = html($this->get('/deputados/101/legislatura/57/'));
    expect(attrs($doc, '.ma-score__col', 'href'))->toBe(['/votacoes/100-1/', '/votacoes/100-2/', '/votacoes/100-3/', '/votacoes/100-6/'])
        ->and(texts($doc, '.ma-score__table tbody td:last-child'))->toBe(['Sim', 'Abstenção', 'Não', 'Votação secreta']);

    expect(attrs(html($this->get('/deputados/101/')), '.ma-score__col', 'href'))->toBe(['/votacoes/300-1/']);

    $senator = html($this->get('/senadores/9101/'));
    expect(attrs($senator, '.ma-score__col', 'href'))->toBe(['/senado/votacoes/6923/', '/senado/votacoes/7001/'])
        ->and(texts($senator, '.ma-score__table tbody td:last-child'))->toBe(['Sim', 'Votou (votação secreta)']);

    $presiding = $this->get('/senadores/9104/');
    $presidingDoc = html($presiding);
    expect(texts($presidingDoc, '.ma-score__table tbody td:last-child')[0])->toBe('Presidente da sessão (art. 51 RISF)')
        ->and((string) $presiding->getContent())->not->toContain('Art. 17');
});

test('senator page says only that no vote was recorded', function () {
    $response = $this->get('/senadores/9103/');
    $doc = html($response);

    expect(texts($doc, '.ma-score__table tbody td:last-child'))->toBe(['Não registrou voto', 'Não registrou voto']);
    $html = (string) $response->getContent();
    $props = json_encode($response->viewData('page')['props'], JSON_UNESCAPED_UNICODE);
    expect($props)->toContain('Teresa Lins');
    foreach (['P-NRV', 'Presente, não registrou voto', 'Licença', 'Registro do Senado'] as $official) {
        expect($html)->not->toContain($official)
            ->and($props)->not->toContain($official);
    }
});

test("member footer carries its house's collection day", function () {
    $footer = fn (string $path) => textOf(html($this->get($path))->querySelector('footer'));

    expect($footer('/deputados/101/'))->toContain('Dados abertos da Câmara dos Deputados, coletados em 01/03/2027')
        ->and($footer('/senadores/9101/'))->toContain('Dados abertos do Senado Federal, coletados em 05/03/2027')
        ->and($footer('/senadores/9101/'))->not->toContain('Câmara');

    $last = ContractImport::query()->where('house', 'camara')->latest('id')->first();
    ContractImport::query()->create([...collect($last->getAttributes())->except(['id', 'created_at', 'updated_at'])->all(), 'generated_at' => '2027-04-10T15:00:00Z']);

    expect($footer('/deputados/101/'))->toContain('Dados abertos da Câmara dos Deputados, coletados em 10/04/2027')
        ->and($footer('/senadores/9101/'))->toContain('Dados abertos do Senado Federal, coletados em 05/03/2027');
});

test('member page draws initials and no remote image', function () {
    foreach (['/deputados/101/' => ['AS', 'bandep/101.jpg'], '/senadores/9101/' => ['RA', 'senador9101.jpg']] as $path => [$initials, $photo]) {
        $response = $this->get($path);
        $doc = html($response);
        expect(textOf($doc->querySelector('.ma-photo__initials')))->toBe($initials)
            ->and($doc->querySelectorAll('img'))->toHaveCount(0)
            ->and((string) $response->getContent())->not->toContain($photo);
    }
});

test('indicators show the merit base then all votes', function () {
    $doc = html($this->get('/deputados/101/legislatura/57/'));
    $groups = iterator_to_array($doc->querySelectorAll('.ma-indicator'));

    expect(array_map(fn ($g) => textOf($g->querySelector('h2')), $groups))->toBe([
        'Participação em votações nominais do plenário', 'Votos iguais à orientação do governo', 'Votos iguais à maioria do próprio partido',
    ]);
    $anchors = ['participacao', 'alinhamento-governo', 'alinhamento-partido'];
    foreach ($groups as $i => $group) {
        expect(texts($group, '.ma-ndem__label'))->toBe(['nas votações sobre propostas e emendas', 'em todas as votações nominais do plenário'])
            ->and(array_values(array_filter(attrs($group, '.ma-note a', 'href'), fn ($h) => str_contains($h, 'metodologia'))))
            ->toBe(array_fill(0, 2, "https://mandato.test/metodologia/#{$anchors[$i]}"));
    }
    expect(texts($doc, '.ma-ndem .ma-ndem__value'))->toBe(['3 de 4', '7 de 10', '2 de 3', '4 de 6', '1 de 2', '5 de 8']);
});

test('member page explains the two bases once', function () {
    foreach (['/deputados/101/', '/deputados/101/legislatura/57/', '/deputados/102/', '/deputados/103/', '/senadores/9101/', '/senadores/9103/'] as $path) {
        $doc = html($this->get($path));
        $matches = array_values(array_filter(iterator_to_array($doc->querySelectorAll('main p')), fn ($p) => textOf($p) === BASES_PARAGRAPH));
        expect($matches)->toHaveCount(1, $path);
        $link = $matches[0]->querySelector('a');
        expect(textOf($link))->toBe('regras publicadas')
            ->and($link->getAttribute('href'))->toBe('/metodologia/#classificacao');
        $position = fn ($node) => $matches[0]->compareDocumentPosition($node);
        expect($position($doc->querySelector('.ma-ndem')) & 4)->toBe(4, $path); // DOCUMENT_POSITION_FOLLOWING
    }
});

test('a basis without total shows no number', function () {
    $values = texts(html($this->get('/deputados/102/')), '.ma-ndem .ma-ndem__value');
    foreach ([0, 2, 4] as $merit) {
        expect($values[$merit])->toBe('Sem base de cálculo no período')
            ->and($values[$merit])->not->toMatch('/\d/');
    }
    expect($values[1])->toBe('3 de 5');

    $ana = texts(html($this->get('/deputados/101/')), '.ma-ndem .ma-ndem__value');
    expect([$ana[2], $ana[3]])->toBe(['Sem base de cálculo no período', 'Sem base de cálculo no período']);
});

test('member page states the symbolic count', function () {
    $text = fn (string $path) => textOf(html($this->get($path))->querySelector('.ma-symbolic'));

    expect($text('/deputados/101/legislatura/57/'))->toBe('Durante o exercício nesta legislatura, o plenário também decidiu 2 votações simbólicas sobre propostas e emendas. Votação simbólica não registra o voto de cada parlamentar.')
        ->and($text('/deputados/102/'))->toBe('Durante o exercício nesta legislatura, o plenário também decidiu 1 votação simbólica sobre propostas e emendas. Votação simbólica não registra o voto de cada parlamentar.')
        ->and($text('/deputados/101/'))->toBe('Nenhuma votação simbólica sobre propostas e emendas ocorreu no plenário durante o exercício nesta legislatura.');
});

test('member page says when a house publishes no symbolic votes', function () {
    $senate = textOf(html($this->get('/senadores/9101/'))->querySelector('main'));
    expect($senate)->toContain('O Senado Federal não publica votações simbólicas como registros de votação; por isso elas não aparecem aqui.')
        ->and($senate)->not->toContain('votações simbólicas sobre propostas')
        ->and($senate)->not->toContain('votação simbólica sobre propostas');

    $bruno = DB::table('members')->where('house', 'camara')->where('source_id', '102')->value('id');
    DB::table('memberships')->where('member_id', $bruno)->where('legislature_number', 57)->update(['symbolic_merit' => null]);

    expect(textOf(html($this->get('/deputados/102/'))->querySelector('.ma-symbolic')))
        ->toBe('A Câmara dos Deputados não publica votações simbólicas como registros de votação; por isso elas não aparecem aqui.');
});

/** The note `#nota-{n}` a marker points to: its source link, its method link and its text. */
function noteOf(HTMLDocument $doc, string $href): array
{
    $note = $doc->getElementById(ltrim($href, '#'));
    $links = iterator_to_array($note->querySelectorAll('a'));

    return [
        'source' => [$links[0]->getAttribute('href'), textOf($links[0])],
        'method' => isset($links[1]) ? [$links[1]->getAttribute('href'), textOf($links[1])] : null,
    ];
}

test('the symbolic count points to its source and method', function () {
    $ana = DB::table('members')->where('house', 'camara')->where('source_id', '101')->value('source_url');
    $bruno = DB::table('members')->where('house', 'camara')->where('source_id', '102')->value('source_url');

    foreach (['/deputados/101/legislatura/57/' => $ana, '/deputados/102/' => $bruno, '/deputados/101/' => $ana] as $path => $source) {
        $doc = html($this->get($path));
        $markers = attrs($doc, '.ma-symbolic .ma-note-ref', 'href');
        expect($markers)->toBe(['#nota-7'], $path)
            ->and(noteOf($doc, $markers[0]))->toBe([
                'source' => [$source, 'Câmara dos Deputados'],
                'method' => ['https://mandato.test/metodologia/#votacoes-simbolicas', 'Como calculamos'],
            ], $path)
            ->and(attrs($doc, 'p.ma-note', 'id'))->toBe(array_map(fn ($i) => "nota-{$i}", range(1, 9)), $path);
    }

    $senate = html($this->get('/senadores/9101/'));
    expect($senate->querySelectorAll('.ma-symbolic .ma-note-ref'))->toHaveCount(0)
        ->and(attrs($senate, '.ma-note a', 'href'))->not->toContain('https://mandato.test/metodologia/#votacoes-simbolicas')
        ->and(attrs($senate, 'p.ma-note', 'id'))->toBe(array_map(fn ($i) => "nota-{$i}", range(1, 8)));
});

test('the profile counts the propositions of the rendered mandate', function () {
    $cases = [
        '/deputados/101/legislatura/57/' => ['2', '1', '1'],
        '/deputados/101/' => ['1', '1', '0'],
        '/deputados/102/' => ['1', '0', '2'],
        '/senadores/9101/' => ['1', '1', '0'],
    ];
    foreach ($cases as $path => $values) {
        $doc = html($this->get($path));
        $stats = iterator_to_array($doc->querySelectorAll('.ma-stat'));
        expect(array_map(fn ($s) => textOf($s->querySelector('.ma-muted')), $stats))->toBe(['Proposições de autoria', 'Como primeiro signatário', 'Requerimentos'], $path)
            ->and(array_map(fn ($s) => textOf($s->querySelector('.ma-num')), $stats))->toBe($values, $path);
        $markers = array_unique(attrs($doc, '.ma-stat .ma-note-ref', 'href'));
        expect($markers)->toHaveCount(1, $path)
            ->and(noteOf($doc, $markers[0])['method'][0])->toBe('https://mandato.test/metodologia/#proposicoes', $path);
    }
});

test('the score caption names no gender', function () {
    $caption = 'Cada traço é uma votação do plenário com registro neste mandato, da mais antiga para a mais recente. Sim fica acima da linha, Não abaixo; as demais opções têm marca própria. Cada traço leva à votação.';
    foreach (['/deputados/101/', '/deputados/103/', '/senadores/9101/', '/senadores/9103/'] as $path) {
        $doc = html($this->get($path));
        $head = array_values(array_filter(iterator_to_array($doc->querySelectorAll('.ma-section__head')), fn ($h) => textOf($h->querySelector('h2')) === 'Votações do mandato'));
        expect($head)->toHaveCount(1, $path)
            ->and(textOf($head[0]->querySelector('p')))->toBe($caption, $path)
            ->and(textOf($doc->querySelector('main')))->not->toMatch('/\b(deste|desta) (deputad|senador)/u', $path);
    }
});
