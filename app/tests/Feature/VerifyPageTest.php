<?php

use App\Cards\Code;
use App\Models\House;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Jpeg;

// share-cards S5: every code opens the data the card showed (C52-C56, C60, C74).

beforeEach(function () {
    Storage::fake('media');
    Process::fake(['*' => Process::result(output: Jpeg::png(1, 1))]);
    importFixtures();
});

/** Serves a card's 1200x630 image, which stores its snapshot (door 8), and returns its code. */
function serveCard(string $subjectPath, string $code): string
{
    test()->get("{$subjectPath}card/{$code}/1200x630.png")->assertStatus(200);

    return $code;
}

/** Changes Ana Souza's vote on `300-1`, her only vote in the 58th legislature. */
function changeAnasVote(string $position, string $official): void
{
    $member = DB::table('members')->where('house', 'camara')->where('source_id', '101')->value('id');
    $rollCall = DB::table('roll_calls')->where('house', 'camara')->where('source_id', '300-1')->value('id');
    DB::table('votes')->where('member_id', $member)->where('roll_call_id', $rollCall)->update(['position' => $position, 'official' => $official]);
}

/** Removes member 101 and every row that references it from the contract tables. */
function deleteAna(): void
{
    $member = DB::table('members')->where('house', 'camara')->where('source_id', '101')->value('id');
    DB::table('votes')->where('member_id', $member)->delete();
    DB::table('memberships')->where('member_id', $member)->delete();
    DB::table('members')->where('id', $member)->delete();
}

test('a stored code opens the card data', function () {
    requireSsr();
    $code = serveCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));

    $response = $this->get("/verificar/{$code}/");

    $response->assertStatus(200);
    $doc = html($response);
    $main = $doc->querySelector('main');
    $images = iterator_to_array($main->querySelectorAll('img'));
    expect(textOf($main->querySelector('h1')))->toBe("Código {$code}")
        ->and($main->querySelectorAll('h1'))->toHaveCount(1)
        ->and($images)->toHaveCount(1)
        ->and($images[0]->getAttribute('src'))->toBe("/deputados/101/legislatura/58/card/{$code}/1200x630.png")
        // C60: the image's alt is the code's AC 41 alt
        ->and($images[0]->getAttribute('alt'))->toBe("Card do Mandato Aberto: Ana Souza (PT-SP), Câmara dos Deputados, 58ª legislatura. Nas votações sobre propostas e emendas: participação em 1 de 1; votos iguais à orientação do governo: sem base de cálculo; votos iguais à maioria do partido em 1 de 1. Dados de 01/03/2027. Código {$code}.");
    $text = textOf($main);
    foreach ([
        'Ana Souza', 'PT · SP', '58ª legislatura', 'Nas votações sobre propostas e emendas',
        'Participação em votações nominais do plenário 1 de 1',
        'Votos iguais à orientação do governo Sem base de cálculo no período',
        'Votos iguais à maioria do próprio partido 1 de 1',
        '15/02/2027 Sim',
        'Dados abertos da Câmara dos Deputados, coletados em 01/03/2027.',
    ] as $value) {
        expect($text)->toContain($value);
    }
    $links = array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($main->querySelectorAll('a')));
    expect($links)->toContain('/deputados/101/legislatura/58/');

    $senate = serveCard('/senado/votacoes/7001/', Code::of(rollCallPayload(House::Senado, '7001')));
    $doc = html($this->get("/verificar/{$senate}/")->assertStatus(200));
    $main = $doc->querySelector('main');
    $text = textOf($main);
    expect(textOf($main->querySelector('h1')))->toBe("Código {$senate}")
        ->and($main->querySelector('img')->getAttribute('src'))->toBe("/senado/votacoes/7001/card/{$senate}/1200x630.png")
        ->and($main->querySelector('img')->getAttribute('alt'))->toBe("Card do Mandato Aberto: Votação secreta de 10/06/2025, Senado Federal, 10/06/2025. Aprovada. 40 Sim, 20 Não, 1 outros votos. Dados de 05/03/2027. Código {$senate}.");
    foreach (['Votação secreta de 10/06/2025', 'Aprovada', '40 Sim · 20 Não · 1 outros votos', 'Dados abertos do Senado Federal, coletados em 05/03/2027.'] as $value) {
        expect($text)->toContain($value);
    }
    expect(array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($main->querySelectorAll('a'))))->toContain('/senado/votacoes/7001/');
});

test('the verification page compares the card with current data', function () {
    requireSsr();
    $code = serveCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));
    $main = fn () => html($this->get("/verificar/{$code}/")->assertStatus(200))->querySelector('main');

    expect(textOf($main()))->toContain('Os dados atuais são iguais aos do card.')
        ->not->toContain('Os dados mudaram')
        ->not->toContain('Este registro não está nos dados atuais.');

    changeAnasVote('no', 'Não');
    $changed = $main();
    $current = array_values(array_filter(iterator_to_array($changed->querySelectorAll('a')), fn ($a) => textOf($a) === 'Ver dados atuais'));
    expect(textOf($changed))->toContain('Os dados mudaram desde 01/03/2027. O card mostra os dados daquela data.')
        ->not->toContain('Os dados atuais são iguais aos do card.')
        ->and($current)->toHaveCount(1)
        ->and($current[0]->getAttribute('href'))->toBe('/deputados/101/legislatura/58/')
        // the page shows what the card showed, not the current vote
        ->and(textOf($changed))->toContain('15/02/2027 Sim');

    deleteAna();
    expect(textOf($main()))->toContain('Este registro não está nos dados atuais.')
        ->not->toContain('Os dados atuais são iguais aos do card.')
        ->not->toContain('Os dados mudaram');
});

test('a verification page whose subject is gone shows no card image', function () {
    requireSsr();
    $code = serveCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));
    expect(html($this->get("/verificar/{$code}/"))->querySelectorAll('body img'))->toHaveCount(1);

    deleteAna();
    $response = $this->get("/verificar/{$code}/");

    $response->assertStatus(200);
    $doc = html($response);
    $text = textOf($doc->querySelector('main'));
    expect($text)->toContain('Este registro não está nos dados atuais.')
        ->toContain('Ana Souza')
        ->toContain('PT · SP')
        ->toContain('1 de 1')
        ->and($doc->querySelectorAll('body img'))->toHaveCount(0);
});

test('a recoverable code variant redirects to its canonical form', function () {
    $code = serveCard('/deputados/101/legislatura/58/', Code::of(memberPayload(House::Camara, '101', 58)));
    $variants = [
        'lowercase' => strtolower($code),
        'spaces' => str_replace('-', '%20', $code),
        'no hyphen' => str_replace('-', '', $code),
        'O for 0' => str_replace('0', 'O', $code),
        'I for 1' => str_replace('1', 'I', $code),
        'L for 1' => str_replace('1', 'L', $code),
    ];

    foreach ($variants as $name => $variant) {
        expect($variant)->not->toBe($code, $name);
        $this->get("/verificar/{$variant}/")
            ->assertStatus(301)
            ->assertHeader('Location', config('app.url')."/verificar/{$code}/");
    }
});

test('an unknown code answers 404 with how a code looks', function () {
    requireSsr();
    foreach (['/verificar/20270301-00000000/', '/verificar/abc/'] as $path) {
        $response = $this->get($path);
        $response->assertStatus(404);
        $main = html($response)->querySelector('main');
        expect(textOf($main->querySelector('h1')))->toBe('Código não encontrado', $path)
            ->and(textOf($main))->toContain('O código tem oito números, um hífen e oito letras ou números, como 20270930-K7Q29XPD.');
    }
});

test('the verification index takes a code', function () {
    requireSsr();
    $response = $this->get('/verificar/');

    $response->assertStatus(200);
    $doc = html($response);
    $forms = iterator_to_array($doc->querySelectorAll('main form'));
    expect($forms)->toHaveCount(1)
        ->and(strtolower($forms[0]->getAttribute('method')))->toBe('get');
    $input = $forms[0]->querySelector('input[name="codigo"]');
    expect($input)->not->toBeNull();
    $label = $doc->querySelector('label[for="'.$input->getAttribute('id').'"]');
    expect($input->getAttribute('id'))->not->toBe('')
        ->and(textOf($label))->toBe('Código de verificação');

    $this->get('/verificar/?codigo='.rawurlencode('20270301 k7q2 9xpd'))
        ->assertStatus(302)
        ->assertHeader('Location', config('app.url').'/verificar/20270301-K7Q29XPD/');
});
