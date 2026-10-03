<?php

use App\Models\House;
use App\Presenters\VoteGroups;

// Check C54 of .specs/features/app-contract-v3/checks.md.

function entry(int $id, string $name, string $position): array
{
    return ['memberId' => (string) $id, 'name' => $name, 'party' => 'P', 'uf' => 'SP', 'href' => "/x/{$id}/", 'position' => $position, 'official' => ''];
}

test('orders vote groups by position', function () {
    $positions = array_reverse(['yes', 'no', 'abstention', 'obstruction', 'presiding', 'secret', 'notVoting']);
    $entries = array_map(fn (string $p, int $i) => entry($i, "Pessoa {$i}", $p), $positions, array_keys($positions));

    $camara = VoteGroups::of($entries, House::Camara);
    expect(array_column($camara, 'position'))->toBe(['yes', 'no', 'abstention', 'obstruction', 'presiding', 'secret', 'notVoting'])
        ->and(array_column($camara, 'label'))->toBe(['Sim', 'Não', 'Abstenção', 'Obstrução', 'Art. 17 (presidente da sessão)', 'Deputados que votaram', 'Sem voto registrado']);

    $senado = VoteGroups::of($entries, House::Senado);
    expect(array_column($senado, 'label'))->toBe(['Sim', 'Não', 'Abstenção', 'Obstrução', 'Presidente da sessão (art. 51 RISF)', 'Senadores que votaram', 'Sem voto registrado']);

    $names = ['Zuleica', 'Érico', 'Abel', 'Ágata', 'Edu'];
    $one = VoteGroups::of(array_map(fn (string $n, int $i) => entry($i, $n, 'yes'), $names, array_keys($names)), House::Camara);

    expect($one)->toHaveCount(1)
        ->and(array_column($one[0]['entries'], 'name'))->toBe(['Abel', 'Ágata', 'Edu', 'Érico', 'Zuleica']);
});
