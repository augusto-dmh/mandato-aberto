<?php

use App\Cards\Code;
use Symfony\Component\Process\Process;

// share-cards door 5: canonical JSON, digest and verification code (C48-C51).

/** A member payload shaped as door 5 lists it. */
function samplePayload(array $change = []): array
{
    return [
        'template' => 1, 'kind' => 'member', 'house' => 'camara', 'sourceId' => '101', 'legislature' => 58,
        'generatedAt' => '2027-03-02T02:30:00Z', 'photoSha256' => str_repeat('a', 64),
        'name' => 'Ana Souza', 'party' => 'PT', 'uf' => 'SP',
        'figures' => [
            ['label' => 'Participação em votações nominais do plenário', 'count' => 1, 'total' => 1],
            ['label' => 'Votos iguais à orientação do governo', 'count' => 0, 'total' => 0],
            ['label' => 'Votos iguais à maioria do próprio partido', 'count' => 1, 'total' => 1],
        ],
        'votes' => [['rollCallId' => '300-1', 'date' => '2027-02-15', 'position' => 'yes']],
        ...$change,
    ];
}

/** Crockford base32 of the first 40 bits of a hex digest, through a string of bits. */
function crockford40(string $hex): string
{
    $bits = '';
    foreach (str_split(substr($hex, 0, 10)) as $nibble) {
        $bits .= str_pad(base_convert($nibble, 16, 2), 4, '0', STR_PAD_LEFT);
    }

    return implode('', array_map(fn (string $five) => '0123456789ABCDEFGHJKMNPQRSTVWXYZ'[bindec($five)], str_split($bits, 5)));
}

test('canonical json sorts keys and refuses floats', function () {
    expect(Code::canonical(['b' => 1, 'a' => ['d' => 'é/', 'c' => [2, 1]]]))->toBe('{"a":{"c":[2,1],"d":"é/"},"b":1}')
        ->and(Code::canonical(['z' => null, 'list' => [['y' => 1, 'x' => 2]]]))->toBe('{"list":[{"x":2,"y":1}],"z":null}');

    expect(fn () => Code::canonical(['a' => 1.5]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Code::canonical(['a' => ['b' => true]]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Code::canonical(['a' => [false]]))->toThrow(InvalidArgumentException::class);
});

test('the code is the brasilia date and forty bits of the digest', function () {
    $payload = samplePayload();
    $canonical = '{"figures":[{"count":1,"label":"Participação em votações nominais do plenário","total":1},{"count":0,"label":"Votos iguais à orientação do governo","total":0},{"count":1,"label":"Votos iguais à maioria do próprio partido","total":1}],'
        .'"generatedAt":"2027-03-02T02:30:00Z","house":"camara","kind":"member","legislature":58,"name":"Ana Souza","party":"PT","photoSha256":"'.str_repeat('a', 64).'",'
        .'"sourceId":"101","template":1,"uf":"SP","votes":[{"date":"2027-02-15","position":"yes","rollCallId":"300-1"}]}';
    $digest = hash('sha256', $canonical);

    expect(Code::canonical($payload))->toBe($canonical)
        ->and(Code::digest($payload))->toBe($digest)
        ->and(Code::of($payload))->toBe('20270301-'.crockford40($digest))
        ->and(Code::of(samplePayload(['generatedAt' => '2027-03-02T03:00:00Z'])))->toStartWith('20270302-');

    $script = 'require "vendor/autoload.php"; echo App\Cards\Code::of(json_decode(stream_get_contents(STDIN), true));';
    $process = new Process(['php', '-r', $script], dirname(__DIR__, 2));
    $process->setInput(json_encode($payload, JSON_UNESCAPED_UNICODE))->mustRun();
    expect($process->getOutput())->toBe(Code::of($payload));
});

test('any shown value changes the code', function (array $change) {
    expect(Code::of(samplePayload($change)))->not->toBe(Code::of(samplePayload()));
})->with([
    'name' => [['name' => 'Ana Souza Lima']],
    'a figure count' => [['figures' => array_replace(samplePayload()['figures'], [0 => ['label' => 'Participação em votações nominais do plenário', 'count' => 0, 'total' => 1]])]],
    'a figure total' => [['figures' => array_replace(samplePayload()['figures'], [0 => ['label' => 'Participação em votações nominais do plenário', 'count' => 1, 'total' => 2]])]],
    'a vote position' => [['votes' => [['rollCallId' => '300-1', 'date' => '2027-02-15', 'position' => 'no']]]],
    'the party' => [['party' => 'PSB']],
    'the photo' => [['photoSha256' => null]],
    'generatedAt' => [['generatedAt' => '2027-03-02T02:31:00Z']],
    'template' => [['template' => 2]],
]);

test('codes normalise case separators and look-alikes', function () {
    foreach ([
        '20270930-k7q29xpd' => '20270930-K7Q29XPD',
        '20270930 K7Q2 9XPD' => '20270930-K7Q29XPD',
        '20270930K7Q29XPD' => '20270930-K7Q29XPD',
        '2O27O93O-K7Q29XPD' => '20270930-K7Q29XPD',
        '20270930-K7Q2IXPD' => '20270930-K7Q21XPD',
        '20270930-K7Q2LXPD' => '20270930-K7Q21XPD',
        '20270930-K7Q29XPD' => '20270930-K7Q29XPD',
    ] as $typed => $canonical) {
        expect(Code::normalise($typed))->toBe($canonical, $typed);
    }
    foreach (['abc', '20270930-K7Q29XP', '20270930-K7Q29XPDD', '2027O93-K7Q29XPDX', '20270930-K7Q29XPU'] as $typed) {
        expect(Code::normalise($typed))->toBeNull($typed);
    }
});
