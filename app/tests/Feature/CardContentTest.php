<?php

use App\Cards\Code;
use App\Cards\Renderer;
use App\Models\House;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Tests\Support\ForbiddenTerms;

// share-cards S4 in the app: what each card says, from the stored data (C38-C42, C44).

/** The card HTML the CLI renders for one subject's current payload, parsed. */
function cardHtml(array $payload, string $format = '1200x630'): HTMLDocument
{
    requireCardRenderer();
    $html = app(Renderer::class)->html(Renderer::input($payload, $format, Code::of($payload)));

    return HTMLDocument::createFromString($html, LIBXML_NOERROR);
}

function cardText(HTMLDocument $doc): string
{
    return textOf($doc->querySelector('.ma-card'));
}

/** Text of each node matching the selectors, in the order given. */
function regionTexts(HTMLDocument $doc, array $selectors): array
{
    return array_map(fn (string $s) => textOf($doc->querySelector(".ma-card {$s}")), $selectors);
}

const MEMBER_REGIONS = ['.ma-card__eyebrow', '.ma-photo', 'h1', '.ma-card__body > p.ma-muted', '.ma-card__basis', '.ma-card__figures', '.ma-card__score-label', '.ma-score', '.ma-card__foot'];

test('member card html shows the mandate in order', function () {
    importFixtures();

    foreach (Renderer::FORMATS as $format => $prop) {
        $doc = cardHtml(memberPayload(House::Camara, '101', 57), $format);
        expect($doc->querySelector(".ma-card.ma-card--{$prop}"))->not->toBeNull()
            ->and($doc->querySelectorAll('.ma-card h1')->length)->toBe(1);
        $nodes = array_map(fn (string $s) => $doc->querySelector(".ma-card {$s}"), MEMBER_REGIONS);
        foreach (array_slice($nodes, 1) as $i => $node) {
            expect($nodes[$i]->compareDocumentPosition($node) & 4)->toBe(4, MEMBER_REGIONS[$i + 1]);
        }
        [$eyebrow, , $name, $party, $basis] = regionTexts($doc, MEMBER_REGIONS);
        expect([$eyebrow, $name, $party, $basis])->toBe(['Mandato Aberto · Câmara dos Deputados · 57ª legislatura', 'Ana Souza', 'PSB · SP', 'Nas votações sobre propostas e emendas'])
            ->and(array_map(fn ($f) => textOf($f), iterator_to_array($doc->querySelectorAll('.ma-card__figure'))))->toBe([
                '3 de 4 Participação em votações nominais do plenário',
                '2 de 3 Votos iguais à orientação do governo',
                '1 de 2 Votos iguais à maioria do próprio partido',
            ])
            ->and(textOf($doc->querySelector('.ma-card__score-label')))->toBe('4 votações nominais do plenário com registro, da mais antiga à mais recente')
            ->and(textOf($doc->querySelector('.ma-photo')))->toBe('AS');
    }

    $doc = cardHtml(memberPayload(House::Senado, '9101', 57));
    expect(regionTexts($doc, ['.ma-card__eyebrow', 'h1', '.ma-card__body > p.ma-muted']))->toBe(['Mandato Aberto · Senado Federal · 57ª legislatura', 'Rosa Andrade', 'PT · SP']);
});

test('member card html empty states', function () {
    importFixtures();

    $doc = cardHtml(memberPayload(House::Camara, '102', 57));
    $figures = array_map(fn ($f) => textOf($f), iterator_to_array($doc->querySelectorAll('.ma-card__figure')));
    expect($figures)->toBe([
        'Participação em votações nominais do plenário: Sem base de cálculo no período',
        'Votos iguais à orientação do governo: Sem base de cálculo no período',
        'Votos iguais à maioria do próprio partido: Sem base de cálculo no período',
    ]);

    $member = DB::table('members')->where('house', 'camara')->where('source_id', '103')->value('id');
    DB::table('votes')->where('member_id', $member)->delete();
    $doc = cardHtml(memberPayload(House::Camara, '103', 57));
    expect($doc->querySelector('.ma-card .ma-score'))->toBeNull()
        ->and(cardText($doc))->toContain('Nenhuma votação nominal do plenário com registro nesta legislatura.');
});

test('roll-call card html shows the result in order', function () {
    importFixtures();
    $regions = ['.ma-card__eyebrow', 'h1', '.ma-card__body > p.ma-muted', '.ma-card__result', '.ma-card__tally', '.ma-card__foot'];

    $doc = cardHtml(rollCallPayload(House::Camara, '100-1'));
    expect(array_slice(regionTexts($doc, $regions), 0, 4))->toBe(['Mandato Aberto · Câmara dos Deputados', 'PL 1/2023', 'Votação nominal · Decisão sobre a proposta · 01/03/2023', 'Aprovada'])
        ->and(textOf($doc->querySelector('.ma-card__tallies')))->toBe('1 Sim · 1 Não · 1 outros votos')
        ->and($doc->querySelector('.ma-card__tally .ma-tally'))->not->toBeNull()
        ->and($doc->querySelectorAll('.ma-card h1')->length)->toBe(1);

    expect(textOf(cardHtml(rollCallPayload(House::Camara, '100-3'))->querySelector('.ma-card__result')))->toBe('Resultado não informado')
        ->and(textOf(cardHtml(rollCallPayload(House::Camara, '100-2'))->querySelector('.ma-card__result')))->toBe('Rejeitada');

    $symbolic = cardHtml(rollCallPayload(House::Camara, '100-4'));
    expect(textOf($symbolic->querySelector('.ma-card__tally')))->toBe('Votação simbólica: não há registro do voto de cada parlamentar nem placar.')
        ->and($symbolic->querySelector('.ma-tally'))->toBeNull();
    $secret = cardHtml(rollCallPayload(House::Camara, '100-5'));
    expect(textOf($secret->querySelector('.ma-card__tally')))->toBe('Placar não publicado pela Casa.')
        ->and($secret->querySelector('.ma-tally'))->toBeNull();

    $senate = cardHtml(rollCallPayload(House::Senado, '7001'));
    expect(textOf($senate->querySelector('.ma-card__eyebrow')))->toBe('Mandato Aberto · Senado Federal')
        ->and(textOf($senate->querySelector('.ma-card__tallies')))->toBe('40 Sim · 20 Não · 1 outros votos');
});

test('every card footer names source date code and address', function () {
    importFixtures();

    foreach ([
        [memberPayload(House::Camara, '101', 58), 'Câmara dos Deputados', '01/03/2027'],
        [rollCallPayload(House::Camara, '100-1'), 'Câmara dos Deputados', '01/03/2027'],
        [memberPayload(House::Senado, '9101', 57), 'Senado Federal', '05/03/2027'],
        [rollCallPayload(House::Senado, '7001'), 'Senado Federal', '05/03/2027'],
    ] as [$payload, $house, $date]) {
        $code = Code::of($payload);
        expect(textOf(cardHtml($payload)->querySelector('.ma-card__foot')))->toBe("Fonte: {$house}, dados de {$date} Código {$code} · confira em mandato.test/verificar/");
    }
});

test('cards never carry what a card must not say', function () {
    importFixtures();
    $names = DB::table('members')->pluck('name', 'source_id')->all();
    $terms = [...config('forbidden-terms'), 'importante', 'relevante', 'posição', 'percentil', 'lugar'];
    $absence = ['Presente, não registrou voto', 'Atividade parlamentar', 'Missão da Casa no País ou no exterior', 'Não compareceu', 'Dispositivo não citado', 'Licença', 'Sem voto:'];
    expect(config('forbidden-terms'))->toHaveCount(19);

    $cards = [];
    foreach (DB::table('members')->get() as $m) {
        $legislature = DB::table('memberships')->where('member_id', $m->id)->max('legislature_number');
        $cards["member {$m->source_id}"] = [memberPayload(House::from($m->house), $m->source_id, (int) $legislature), $m->source_id];
    }
    foreach (DB::table('roll_calls')->get() as $r) {
        $cards["roll call {$r->source_id}"] = [rollCallPayload(House::from($r->house), $r->source_id), null];
    }
    expect($cards)->toHaveCount(19);

    foreach ($cards as $label => [$payload, $own]) {
        $text = cardText(cardHtml($payload));
        // one needle per assertion: `toContain` reads every further argument as another needle, not a message
        expect(str_contains($text, '%'))->toBeFalse("{$label}: %")
            ->and(ForbiddenTerms::inText($text, $terms))->toBe([], $label);
        foreach ($names as $id => $name) {
            if ((string) $id !== $own) {
                expect(str_contains($text, $name))->toBeFalse("{$label}: {$name}");
            }
        }
        foreach ($absence as $a) {
            expect(str_contains($text, $a))->toBeFalse("{$label}: {$a}");
        }
        preg_match_all('#\b(?:https?://|www\.)\S+|\b[a-z0-9-]+(?:\.[a-z0-9-]+)+/\S*#i', $text, $urls);
        expect($urls[0])->toBe(['mandato.test/verificar/'], $label);
    }
});

test('two members share one card tree', function () {
    importFixtures();
    $shape = function (HTMLDocument $doc) {
        $out = [];
        foreach ($doc->querySelectorAll('.ma-card *') as $el) {
            if ($el->closest('.ma-score__col') !== null || ($el->parentElement?->closest('.ma-photo') !== null)) {
                continue;
            }
            $out[] = $el->tagName.'.'.$el->getAttribute('class');
        }

        return $out;
    };

    expect($shape(cardHtml(memberPayload(House::Camara, '103', 57))))->toBe($shape(cardHtml(memberPayload(House::Camara, '101', 57))));
});
