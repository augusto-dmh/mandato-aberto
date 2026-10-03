<?php

use App\Cards\Code;
use App\Models\House;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Jpeg;

// share-cards S6: links preview the card and readers can download it (C57-C65).

beforeEach(function () {
    Storage::fake('media');
    importFixtures();
});

/**
 * The card tags of AC 41 in a head, by name; each a list, so a repeated tag shows.
 *
 * @return array<string, list<string>>
 */
function cardTags(HTMLDocument $doc): array
{
    $tags = [];
    foreach (['og:image', 'og:image:width', 'og:image:height', 'og:image:type', 'og:image:alt'] as $property) {
        $tags[$property] = array_map(fn ($m) => (string) $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll("head meta[property=\"{$property}\"]")));
    }
    foreach (['twitter:card', 'twitter:image:alt', 'robots'] as $name) {
        $tags[$name] = array_map(fn ($m) => (string) $m->getAttribute('content'), iterator_to_array($doc->querySelectorAll("head meta[name=\"{$name}\"]")));
    }

    return $tags;
}

/**
 * Every member and roll-call page of the fixtures with its card's subject path and current code.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function cardPages(): array
{
    $pages = [];
    foreach ([['/deputados/101/', House::Camara, '101', 58], ['/deputados/101/legislatura/57/', House::Camara, '101', 57], ['/deputados/102/', House::Camara, '102', 57],
        ['/deputados/103/', House::Camara, '103', 57], ['/senadores/9101/', House::Senado, '9101', 57], ['/senadores/9103/', House::Senado, '9103', 57]] as [$path, $house, $id, $n]) {
        $subject = ($house === House::Senado ? "/senadores/{$id}/" : "/deputados/{$id}/")."legislatura/{$n}/";
        $pages[$path] = [$subject, Code::of(memberPayload($house, $id, $n))];
    }
    foreach (['100-1', '100-2', '100-3', '100-4', '100-5', '100-6', '200-1', '300-1'] as $id) {
        $pages["/votacoes/{$id}/"] = ["/votacoes/{$id}/", Code::of(rollCallPayload(House::Camara, $id))];
    }
    foreach (['6923', '7001'] as $id) {
        $pages["/senado/votacoes/{$id}/"] = ["/senado/votacoes/{$id}/", Code::of(rollCallPayload(House::Senado, $id))];
    }

    return $pages;
}

/** @return array<string, list<string>> the tags C57 expects for a card image and its alt */
function expectedCardTags(string $subjectPath, string $code, string $alt): array
{
    return [
        'og:image' => ["https://mandato.test{$subjectPath}card/{$code}/1200x630.png"],
        'og:image:width' => ['1200'],
        'og:image:height' => ['630'],
        'og:image:type' => ['image/png'],
        'og:image:alt' => [$alt],
        'twitter:card' => ['summary_large_image'],
        'twitter:image:alt' => [$alt],
    ];
}

/** The tags of `cardTags` without `robots`, which only the verification page sets. */
function previewTags(HTMLDocument $doc): array
{
    $tags = cardTags($doc);
    unset($tags['robots']);

    return $tags;
}

test('member pages preview their card', function () {
    $ana58 = Code::of(memberPayload(House::Camara, '101', 58));
    expect(previewTags(html($this->get('/deputados/101/'))))->toBe(expectedCardTags('/deputados/101/legislatura/58/', $ana58,
        "Card do Mandato Aberto: Ana Souza (PT-SP), Câmara dos Deputados, 58ª legislatura. Nas votações sobre propostas e emendas: participação em 1 de 1; votos iguais à orientação do governo: sem base de cálculo; votos iguais à maioria do partido em 1 de 1. Dados de 01/03/2027. Código {$ana58}."));

    $ana57 = Code::of(memberPayload(House::Camara, '101', 57));
    expect(previewTags(html($this->get('/deputados/101/legislatura/57/'))))->toBe(expectedCardTags('/deputados/101/legislatura/57/', $ana57,
        "Card do Mandato Aberto: Ana Souza (PSB-SP), Câmara dos Deputados, 57ª legislatura. Nas votações sobre propostas e emendas: participação em 3 de 4; votos iguais à orientação do governo em 2 de 3; votos iguais à maioria do partido em 1 de 2. Dados de 01/03/2027. Código {$ana57}."));

    $rosa = Code::of(memberPayload(House::Senado, '9101', 57));
    $tags = previewTags(html($this->get('/senadores/9101/')));
    expect($tags['og:image'])->toBe(["https://mandato.test/senadores/9101/legislatura/57/card/{$rosa}/1200x630.png"])
        ->and($tags['twitter:card'])->toBe(['summary_large_image'])
        ->and($tags['og:image:alt'][0])->toStartWith('Card do Mandato Aberto: Rosa Andrade (PT-SP), Senado Federal, 57ª legislatura. Nas votações sobre propostas e emendas: ')
        ->toEndWith(". Dados de 05/03/2027. Código {$rosa}.")
        ->and($tags['twitter:image:alt'])->toBe($tags['og:image:alt']);
});

test('roll-call pages preview their card', function () {
    $code = fn (House $house, string $id) => Code::of(rollCallPayload($house, $id));

    $c = $code(House::Camara, '100-1');
    expect(previewTags(html($this->get('/votacoes/100-1/'))))->toBe(expectedCardTags('/votacoes/100-1/', $c,
        "Card do Mandato Aberto: PL 1/2023, Câmara dos Deputados, 01/03/2023. Aprovada. 1 Sim, 1 Não, 1 outros votos. Dados de 01/03/2027. Código {$c}."));

    foreach (['100-4' => 'Votação simbólica: não há registro do voto de cada parlamentar nem placar.', '100-5' => 'Placar não publicado pela Casa.'] as $id => $sentence) {
        $c = $code(House::Camara, $id);
        $alt = previewTags(html($this->get("/votacoes/{$id}/")))['og:image:alt'];
        expect($alt)->toHaveCount(1)
            ->and($alt[0])->toStartWith('Card do Mandato Aberto: ')
            ->toContain(', Câmara dos Deputados, ')
            ->toContain(" {$sentence} Dados de 01/03/2027. Código {$c}.")
            ->not->toContain(' Sim, ');
    }

    $s = $code(House::Senado, '7001');
    expect(previewTags(html($this->get('/senado/votacoes/7001/'))))->toBe(expectedCardTags('/senado/votacoes/7001/', $s,
        "Card do Mandato Aberto: Votação secreta de 10/06/2025, Senado Federal, 10/06/2025. Aprovada. 40 Sim, 20 Não, 1 outros votos. Dados de 05/03/2027. Código {$s}."));
});

test('pages offer the three card images and the code', function () {
    requireSsr();
    $pages = cardPages();
    expect($pages)->toHaveCount(16);

    foreach ($pages as $path => [$subject, $code]) {
        $doc = html($this->get($path)->assertOk());
        $sections = array_values(array_filter(iterator_to_array($doc->querySelectorAll('main section')),
            fn ($s) => textOf($s->querySelector('h2')) === 'Imagens para compartilhar'));
        expect($sections)->toHaveCount(1, $path);
        $section = $sections[0];
        $notes = iterator_to_array($doc->querySelectorAll('main .ma-note'));
        expect($notes, $path)->not->toBeEmpty()
            // DOCUMENT_POSITION_FOLLOWING: the section comes after the last source note
            ->and(end($notes)->compareDocumentPosition($section) & 4)->toBe(4, $path);

        $links = array_map(fn ($a) => [textOf($a), $a->getAttribute('href'), $a->hasAttribute('download')], iterator_to_array($section->querySelectorAll('a')));
        expect($links)->toBe([
            ['Horizontal, 1200 × 630', "{$subject}card/{$code}/1200x630.png", true],
            ['Feed, 1080 × 1350', "{$subject}card/{$code}/1080x1350.png", true],
            ['Stories, 1080 × 1920', "{$subject}card/{$code}/1080x1920.png", true],
            ["Código de verificação: {$code}", "/verificar/{$code}/", false],
        ], $path);
    }
});

test('rendering a page stores no snapshot', function () {
    $pages = array_keys(cardPages());
    expect($pages)->toHaveCount(16);

    foreach ($pages as $path) {
        $this->get($path)->assertOk();
    }

    expect(DB::table('card_snapshots')->count())->toBe(0);
});

test('card tags survive an ssr outage', function () {
    $expected = [];
    foreach (['/deputados/101/', '/senadores/9101/', '/votacoes/100-1/', '/senado/votacoes/7001/'] as $path) {
        $expected[$path] = previewTags(html($this->get($path)));
    }
    config(['inertia.ssr.url' => 'http://127.0.0.1:9']);

    foreach ($expected as $path => $tags) {
        $response = $this->get($path);
        $response->assertOk();
        $doc = html($response);
        [$subject, $code] = cardPages()[$path];
        expect($doc->getElementById('app')->childElementCount)->toBe(0, $path)
            ->and(previewTags($doc))->toBe($tags, $path)
            ->and($tags['og:image'])->toBe(["https://mandato.test{$subject}card/{$code}/1200x630.png"])
            ->and($tags['twitter:card'])->toBe(['summary_large_image'])
            ->and($tags['og:image:alt'][0])->toStartWith('Card do Mandato Aberto: ')->toEndWith("Código {$code}.");
    }
});

test('only card pages carry a card image', function () {
    Process::fake(['*' => Process::result(output: Jpeg::png(1, 1))]);
    $code = Code::of(memberPayload(House::Camara, '101', 58));
    $this->get("/deputados/101/legislatura/58/card/{$code}/1200x630.png")->assertOk();
    $memberTags = previewTags(html($this->get('/deputados/101/')));

    $verify = cardTags(html($this->get("/verificar/{$code}/")->assertOk()));
    expect($verify['robots'])->toBe(['noindex']);
    unset($verify['robots']);
    expect($verify)->toBe($memberTags);

    foreach (['/metodologia/' => 200, '/verificar/' => 200, '/verificar/20270301-00000000/' => 404] as $path => $status) {
        $tags = cardTags(html($this->get($path)->assertStatus($status)));
        expect($tags['twitter:card'])->toBe(['summary'], $path)
            ->and($tags['og:image'])->toBe([], $path)
            ->and($tags['og:image:alt'])->toBe([], $path)
            ->and($tags['twitter:image:alt'])->toBe([], $path);
    }
});

test('new routes set no cookie', function () {
    storeFixturePhotos();
    $sha = DB::table('photo_versions')->where('house', 'camara')->where('member_source_id', '102')->value('sha256');
    $suppressed = DB::table('photo_versions')->where('house', 'camara')->where('member_source_id', '101')->value('sha256');
    config(['mandato.photo_suppressed' => ['camara:101']]);
    $code = Code::of(memberPayload(House::Camara, '102', 57));
    $card = "/deputados/102/legislatura/57/card/{$code}";
    $verify = fn () => $this->get("/verificar/{$code}/");

    Process::fake(['*' => Process::result(output: Jpeg::png(1, 1))]);
    $responses = [
        'photo 200' => [$this->get("/fotos/{$sha}.jpg"), 200],
        'photo 404' => [$this->get('/fotos/'.str_repeat('0', 64).'.jpg'), 404],
        'photo 410' => [$this->get("/fotos/{$suppressed}.jpg"), 410],
        'card 200' => [$this->get("{$card}/1200x630.png"), 200],
        'card 302' => [$this->get('/deputados/102/legislatura/57/card/20270301-00000000/1200x630.png'), 302],
        'card 404' => [$this->get("/deputados/999/legislatura/57/card/{$code}/1200x630.png"), 404],
    ];
    Process::fake(['*' => Process::result(output: '', errorOutput: 'boom', exitCode: 1)]);
    $responses['card 503'] = [$this->get("{$card}/1080x1350.png"), 503];
    $responses += [
        'verify index 200' => [$this->get('/verificar/'), 200],
        'verify index 302' => [$this->get('/verificar/?codigo='.strtolower($code)), 302],
        'verify 200' => [$verify(), 200],
        'verify 301' => [$this->get('/verificar/'.strtolower($code).'/'), 301],
        'verify 404' => [$this->get('/verificar/20270301-00000000/'), 404],
        'member page' => [$this->get('/deputados/102/'), 200],
        'roll-call page' => [$this->get('/votacoes/100-1/'), 200],
        'page 404' => [$this->get('/deputados/999/'), 404],
    ];

    expect($responses)->toHaveCount(15);
    foreach ($responses as $name => [$response, $status]) {
        expect($response->status())->toBe($status, $name)
            ->and($response->headers->has('Set-Cookie'))->toBeFalse($name);
    }
});
