<?php

use App\Models\RollCall;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Database\Seeders\BudgetSeeder;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;

// Checks C23-C36 and C51 of .specs/features/app-home/checks.md.

const CAMARA_57_COUNTS = [
    '3 votações nominais no plenário',
    '2 votações secretas no plenário',
    '1 votação simbólica no plenário',
    '3 parlamentares com mandato na legislatura',
    '2 proposições apresentadas por parlamentares (PL, PLP, PEC, PDL e PRC)',
];

const SENADO_57_COUNTS = [
    '1 votação nominal no plenário',
    '1 votação secreta no plenário',
    'Votações simbólicas no plenário: não publicadas pela Casa.',
    '6 parlamentares com mandato na legislatura',
    '1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRS)',
];

const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

function overviewDoc(string $n): HTMLDocument
{
    $response = test()->get("/legislaturas/{$n}/");
    $response->assertOk();

    return html($response);
}

/** The section of `$house` ("Câmara dos Deputados" or "Senado Federal") on an overview. */
function houseSection(HTMLDocument $doc, string $house): ?Element
{
    foreach ($doc->querySelectorAll('.ma-house') as $section) {
        if (textOf($section->querySelector('h2')) === $house) {
            return $section;
        }
    }

    return null;
}

/** @return array<string, list<array{text: string, height: string}>> cells per year label */
function calendarCells(?Element $section): array
{
    $years = [];
    foreach ($section?->querySelectorAll('.ma-cal__year') ?? [] as $row) {
        $years[textOf($row->querySelector('.ma-cal__label'))] = array_map(function ($cell) {
            preg_match('/height:\s*([\d.]+%)/', (string) $cell->querySelector('.ma-cal__bar')?->getAttribute('style'), $m);

            return ['text' => textOf($cell), 'height' => $m[1] ?? ''];
        }, iterator_to_array($row->querySelectorAll('.ma-cal__cell')));
    }

    return $years;
}

/** @return list<list<string>> */
function calendarTableRows(?Element $section): array
{
    return array_map(fn ($tr) => textsOf($tr, 'td, th'), iterator_to_array($section?->querySelectorAll('.ma-cal-table tbody tr') ?? []));
}

beforeEach(function () {
    requireSsr();
});

test('overview counts each house activity', function () {
    importFixtures();

    $response = $this->get('/legislaturas/57/');
    $response->assertOk();
    expect($response->viewData('page')['component'])->toBe('Legislatures/Show');
    $doc = html($response);
    expect($doc->querySelectorAll('h1'))->toHaveCount(1)
        ->and(textOf($doc->querySelector('h1')))->toBe('57ª legislatura')
        ->and(textOf($doc->querySelector('.ma-legislature__dates')))->toBe('De 01/02/2023 a 31/01/2027')
        ->and(textsOf($doc->documentElement, '.ma-house > h2'))->toBe(['Câmara dos Deputados', 'Senado Federal'])
        ->and(textsOf(houseSection($doc, 'Câmara dos Deputados'), '.ma-count'))->toBe(CAMARA_57_COUNTS)
        ->and(textsOf(houseSection($doc, 'Senado Federal'), '.ma-count'))->toBe(SENADO_57_COUNTS);

    expect(textsOf(houseSection(overviewDoc('58'), 'Câmara dos Deputados'), '.ma-count'))->toBe([
        '1 votação nominal no plenário',
        '0 votações secretas no plenário',
        '0 votações simbólicas no plenário',
        '1 parlamentar com mandato na legislatura',
        '1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRC)',
    ]);
});

test('overview counts carry their source note', function () {
    importFixtures();
    $doc = overviewDoc('57');
    $notes = function (string $house) use ($doc) {
        $out = [];
        foreach (houseSection($doc, $house)->querySelectorAll('.ma-count') as $count) {
            $note = $count->nextElementSibling;
            if ($note !== null && str_contains((string) $note->getAttribute('class'), 'ma-note')) {
                $out[] = [textOf($note), array_map(fn ($a) => [textOf($a), (string) $a->getAttribute('href')], iterator_to_array($note->querySelectorAll('a')))];
            }
        }

        return $out;
    };
    $note = fn (string $label, string $url, string $day, string $anchor) => [
        "Fonte: {$label}, dados de {$day} · Como calculamos",
        [[$label, $url], ['Como calculamos', "https://mandato.test/metodologia/#{$anchor}"]],
    ];
    $camara = fn (string $anchor) => $note('Câmara dos Deputados', 'https://dadosabertos.camara.leg.br/', '01/03/2027', $anchor);
    $senado = fn (string $anchor) => $note('Senado Federal', 'https://legis.senado.leg.br/dadosabertos/', '05/03/2027', $anchor);

    expect($notes('Câmara dos Deputados'))->toBe([
        $camara('tipos-de-votacao'), $camara('tipos-de-votacao'), $camara('tipos-de-votacao'), $camara('cobertura'), $camara('proposicoes'),
    ])->and($notes('Senado Federal'))->toBe([
        $senado('tipos-de-votacao'), $senado('tipos-de-votacao'), $senado('cobertura'), $senado('proposicoes'),
    ]);
    $ids = array_map(fn ($n) => $n->getAttribute('id'), iterator_to_array($doc->querySelectorAll('.ma-note')));
    expect($ids)->toHaveCount(9)
        ->and(array_unique($ids))->toHaveCount(9);
});

test('overview says the senate publishes no symbolic votes', function () {
    importFixtures();
    $doc = overviewDoc('57');

    $senate = textsOf(houseSection($doc, 'Senado Federal'), '.ma-count');
    expect($senate[2])->toBe('Votações simbólicas no plenário: não publicadas pela Casa.')
        ->and(preg_grep('/^\d+ vota(ção|ções) simbólica/u', $senate))->toBe([])
        ->and(textOf(houseSection($doc, 'Câmara dos Deputados')))->not->toContain('não publicadas pela Casa');
});

test('overview calendar counts roll calls by month', function () {
    importFixtures();
    $doc = overviewDoc('57');
    $cells = fn (int $year, int $from, int $to, array $ones) => array_map(
        fn ($m) => ['text' => MONTHS[$m - 1].' '.(in_array($m, $ones, true) ? '1' : '0'), 'height' => in_array($m, $ones, true) ? '100%' : '0%'],
        range($from, $to),
    );

    $camara = houseSection($doc, 'Câmara dos Deputados');
    expect(textOf($camara->querySelector('.ma-cal h3')))->toBe('Votações nominais e secretas no plenário, por mês')
        ->and(calendarCells($camara))->toBe([
            '2023' => $cells(2023, 2, 12, [3, 5]),
            '2024' => $cells(2024, 1, 12, [3]),
            '2025' => $cells(2025, 1, 9, [8, 9]),
        ]);
    expect(calendarCells(houseSection($doc, 'Senado Federal')))->toBe([
        '2023' => $cells(2023, 2, 12, []),
        '2024' => $cells(2024, 1, 12, []),
        '2025' => $cells(2025, 1, 6, [4, 6]),
    ]);
});

test('overview calendar counts roll calls by month, over the budget dataset', function () {
    $this->seed(BudgetSeeder::class);
    $budget = houseSection(overviewDoc('58'), 'Câmara dos Deputados');
    $months = RollCall::query()->where('house', 'camara')->where('legislature_number', 58)->where('organ', 'PLEN')
        ->whereIn('ballot', ['nominal', 'secret'])->get()
        ->countBy(fn (RollCall $r) => $r->date->format('Y-m'));
    $highest = $months->max();
    $flat = array_merge(...array_values(calendarCells($budget)));
    expect($flat)->toHaveCount(48);
    $i = 0;
    foreach (range(0, 47) as $k) {
        $month = CarbonImmutable::create(2027, 2, 1)->addMonths($k)->format('Y-m');
        $count = $months[$month] ?? 0;
        $height = round($count * 100 / $highest, 1);
        expect($flat[$i]['height'])->toBe(rtrim(rtrim(number_format($height, 1, '.', ''), '0'), '.').'%', $month)
            ->and($flat[$i]['text'])->toEndWith(" {$count}");
        $i++;
    }
});

test('overview calendar bars use a neutral colour', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    preg_match_all('/([^{}]*\.ma-cal[^{}]*)\{([^}]*)\}/', $css, $rules, PREG_SET_ORDER);
    expect($rules)->not->toBeEmpty();
    $bar = array_values(array_filter($rules, fn ($r) => preg_match('/\.ma-cal__bar\b/', $r[1]) === 1));
    expect($bar)->not->toBeEmpty()
        ->and(implode(' ', array_column($bar, 2)))->toMatch('/background(-color)?:\s*var\(--ma-color-muted\)/');
    foreach ($rules as $rule) {
        expect($rule[2])->not->toContain('--ma-color-accent');
    }
});

test('overview table repeats the calendar with voting days', function () {
    importFixtures();
    $camara = houseSection(overviewDoc('57'), 'Câmara dos Deputados');
    $names = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $ones = ['2023-03', '2023-05', '2024-03', '2025-08', '2025-09'];
    $expected = [];
    for ($d = CarbonImmutable::create(2023, 2, 1); $d->format('Y-m') <= '2025-09'; $d = $d->addMonth()) {
        $one = in_array($d->format('Y-m'), $ones, true) ? '1' : '0';
        $expected[] = [$names[$d->month - 1].' de '.$d->year, $one, $one];
    }

    expect(textsOf($camara->querySelector('.ma-cal-table thead'), 'th'))->toBe(['Mês', 'Votações', 'Dias com votação'])
        ->and(calendarTableRows($camara))->toHaveCount(32)
        ->and(calendarTableRows($camara))->toBe($expected);
});

test('overview table repeats the calendar with voting days, over the budget dataset', function () {
    $this->seed(BudgetSeeder::class);
    $budget = calendarTableRows(houseSection(overviewDoc('58'), 'Câmara dos Deputados'));
    $days = RollCall::query()->where('house', 'camara')->where('legislature_number', 58)->where('organ', 'PLEN')
        ->whereIn('ballot', ['nominal', 'secret'])->get()
        ->groupBy(fn (RollCall $r) => $r->date->format('Y-m'))
        ->map(fn ($rs) => $rs->map(fn (RollCall $r) => $r->date->toDateString())->unique()->count());
    expect($budget)->toHaveCount(48);
    foreach ($budget as $k => $row) {
        $month = CarbonImmutable::create(2027, 2, 1)->addMonths($k)->format('Y-m');
        expect($row[2])->toBe((string) ($days[$month] ?? 0), $month);
    }
});

test('overview lists the latest nominal and secret roll calls', function () {
    importFixtures();
    $doc = overviewDoc('57');
    $items = fn (string $house) => array_map(
        fn ($a) => [textOf($a), (string) $a->getAttribute('href')],
        iterator_to_array(houseSection($doc, $house)->querySelectorAll('.ma-recent li a')),
    );

    expect(textOf(houseSection($doc, 'Câmara dos Deputados')->querySelector('.ma-recent h3')))->toBe('Votações nominais e secretas mais recentes')
        ->and($items('Câmara dos Deputados'))->toBe([
            ['01/09/2025 · Votação secreta de 01/09/2025 · Votação secreta · Sem regra correspondente · Aprovada', '/votacoes/100-6/'],
            ['01/08/2025 · Votação secreta de 01/08/2025 · Votação secreta · Decisão sobre a proposta · Aprovada', '/votacoes/100-5/'],
            ['01/03/2024 · PL 1/2023 · Votação nominal · Procedimento · Resultado não informado', '/votacoes/100-3/'],
            ['10/05/2023 · Votação nominal de 10/05/2023 · Votação nominal · Emenda, destaque ou parte do texto · Rejeitada', '/votacoes/100-2/'],
            ['01/03/2023 · PL 1/2023 · Votação nominal · Decisão sobre a proposta · Aprovada', '/votacoes/100-1/'],
        ])
        ->and($items('Senado Federal'))->toBe([
            ['10/06/2025 · Votação secreta de 10/06/2025 · Votação secreta · Decisão sobre a proposta · Aprovada', '/senado/votacoes/7001/'],
            ['01/04/2025 · PL 1/2025 · Votação nominal · Sem regra correspondente · Aprovada', '/senado/votacoes/6923/'],
        ]);
});

test('overview recent list stops at 10', function () {
    $this->seed(BudgetSeeder::class);
    $doc = overviewDoc('58');

    foreach (['camara' => 'Câmara dos Deputados', 'senado' => 'Senado Federal'] as $house => $name) {
        $expected = RollCall::query()->where('house', $house)->where('legislature_number', 58)
            ->when($house === 'camara', fn ($q) => $q->where('organ', 'PLEN'))
            ->whereIn('ballot', ['nominal', 'secret'])->get()
            ->sort(fn (RollCall $a, RollCall $b) => strcmp($b->date->toDateString(), $a->date->toDateString()) ?: strnatcmp($b->source_id, $a->source_id))
            ->take(10)
            ->map(fn (RollCall $r) => PublicUrl::rollCall($house, $r->source_id))
            ->values()->all();
        $links = array_map(fn ($a) => (string) $a->getAttribute('href'), iterator_to_array(houseSection($doc, $name)->querySelectorAll('.ma-recent li a')));
        expect($links)->toHaveCount(10)
            ->and($links)->toBe($expected);
    }
});

test('overview says when a house has no data', function () {
    importFixtures();
    $section = fn (HTMLDocument $doc, string $house) => houseSection($doc, $house);
    $only = function (?Element $s, string $text) {
        expect(array_map(fn ($e) => textOf($e), iterator_to_array($s->children)))->toBe([textOf($s?->querySelector('h2')), $text])
            ->and($s->querySelectorAll('.ma-count, .ma-cal, .ma-cal-table, .ma-recent'))->toHaveCount(0);
    };

    $only($section(overviewDoc('58'), 'Senado Federal'), 'Ainda não há dados do Senado Federal para a 58ª legislatura.');
});

test('overview says when a house has no data, camara only', function () {
    $result = runImport(['dir' => fixtureDir('camara')]);
    expect($result['code'])->toBe(0, $result['err']);
    $senate = houseSection(overviewDoc('57'), 'Senado Federal');

    expect(array_map(fn ($e) => textOf($e), iterator_to_array($senate->children)))->toBe(['Senado Federal', 'Ainda não há dados do Senado Federal para a 57ª legislatura.'])
        ->and($senate->querySelectorAll('.ma-count, .ma-cal, .ma-cal-table, .ma-recent'))->toHaveCount(0);
});

test('overview says when a house has no data, senate only', function () {
    $result = runImport(['dir' => fixtureDir('senado')]);
    expect($result['code'])->toBe(0, $result['err']);
    $camara = houseSection(overviewDoc('57'), 'Câmara dos Deputados');

    expect(array_map(fn ($e) => textOf($e), iterator_to_array($camara->children)))->toBe(['Câmara dos Deputados', 'Ainda não há dados da Câmara dos Deputados para a 57ª legislatura.'])
        ->and($camara->querySelectorAll('.ma-count, .ma-cal, .ma-cal-table, .ma-recent'))->toHaveCount(0);
});

test('overview without nominal roll calls says until when', function () {
    importFixtures();
    RollCall::query()->where('house', 'camara')->where('source_id', '300-1')->delete();
    $camara = houseSection(overviewDoc('58'), 'Câmara dos Deputados');

    expect(textsOf($camara, '.ma-count'))->toBe([
        '0 votações nominais no plenário',
        '0 votações secretas no plenário',
        '0 votações simbólicas no plenário',
        '1 parlamentar com mandato na legislatura',
        '1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRC)',
    ])->and(textOf($camara))->toContain('Nenhuma votação nominal ou secreta no plenário até 01/03/2027.')
        ->and($camara->querySelectorAll('.ma-cal, .ma-cal-table, .ma-recent'))->toHaveCount(0);
});

test('overview navigates between legislatures', function () {
    importFixtures();
    $nav = overviewDoc('57')->querySelectorAll('nav[aria-label="Legislaturas"]');

    expect($nav)->toHaveCount(1)
        ->and(array_map(fn ($a) => [textOf($a), $a->getAttribute('href'), $a->getAttribute('aria-current')], iterator_to_array($nav->item(0)->querySelectorAll('a'))))
        ->toBe([
            ['58ª legislatura (2027–2031)', '/legislaturas/58/', null],
            ['57ª legislatura (2023–2027)', '/legislaturas/57/', 'page'],
        ]);
});

test('overview navigates between legislatures, one stored', function () {
    $result = runImport(['dir' => fixtureDir('senado')]);
    expect($result['code'])->toBe(0, $result['err']);

    expect(overviewDoc('57')->querySelectorAll('nav[aria-label="Legislaturas"]'))->toHaveCount(0);
});

test('overview names no person', function () {
    importSearchFixture();
    $names = DB::table('members')->pluck('name')->all();
    expect($names)->toHaveCount(18);

    foreach (['57', '58'] as $n) {
        $response = $this->get("/legislaturas/{$n}/");
        $content = html_entity_decode((string) $response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            .json_encode($response->viewData('page')['props'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $doc = html($response);
        foreach ($names as $name) {
            expect($content)->not->toContain($name);
        }
        foreach (['participação', 'alinhamento', 'percentual', 'distribuição', 'histograma'] as $word) {
            expect(mb_strtolower($content))->not->toContain($word);
        }
        expect($doc->querySelectorAll('a[href^="/deputados/"], a[href^="/senadores/"], img'))->toHaveCount(0);
        foreach ($doc->getElementById('app')->querySelectorAll('*') as $element) {
            expect((string) $element->textContent)->not->toContain('%');
        }
    }
});

test('overview answers 404 for a legislature not stored', function () {
    importFixtures();

    foreach (['/legislaturas/56/', '/legislaturas/59/', '/legislaturas/abc/', '/legislaturas/5a/', '/legislaturas/'] as $path) {
        $response = $this->get($path);
        $response->assertNotFound();
        expect(textOf(html($response)->querySelector('h1')))->toBe('Página não encontrada', $path);
    }
});

test('overview head tags', function () {
    importFixtures();
    $doc = html($this->get('/legislaturas/57/'));
    $tags = headTags($doc);
    $title = '57ª legislatura: votações e proposições na Câmara e no Senado';
    $description = 'Quantas votações nominais, secretas e simbólicas e quantas proposições houve na 57ª legislatura, por Casa, com dados oficiais e a fonte de cada número.';

    expect($tags['title'])->toBe(["{$title} - Mandato Aberto"])
        ->and($tags['og:title'])->toBe([$title])
        ->and($tags['description'])->toBe([$description])
        ->and($tags['og:description'])->toBe([$description])
        ->and($tags['canonical'])->toBe(['https://mandato.test/legislaturas/57/'])
        ->and($tags['og:url'])->toBe(['https://mandato.test/legislaturas/57/'])
        ->and($doc->querySelectorAll('meta[name="robots"]'))->toHaveCount(0);
});

test('overview and home scale to the budget dataset', function () {
    $this->seed(BudgetSeeder::class);

    $home = html($this->get('/'));
    expect(textsOf(houseSection($home, 'Câmara dos Deputados'), '.ma-count'))->toBe(['1.350 votações nominais no plenário', '600 parlamentares com mandato na legislatura'])
        ->and(textsOf(houseSection($home, 'Senado Federal'), '.ma-count'))->toBe(['1.350 votações nominais no plenário', '81 parlamentares com mandato na legislatura']);

    $doc = overviewDoc('58');
    foreach (['Câmara dos Deputados', 'Senado Federal'] as $house) {
        $section = houseSection($doc, $house);
        $rows = calendarTableRows($section);
        expect(textsOf($section, '.ma-count')[1])->toBe('150 votações secretas no plenário')
            ->and(array_merge(...array_values(calendarCells($section))))->toHaveCount(48)
            ->and($rows)->toHaveCount(48)
            ->and($rows[0][0])->toBe('fevereiro de 2027')
            ->and($rows[47][0])->toBe('janeiro de 2031');
    }
});
