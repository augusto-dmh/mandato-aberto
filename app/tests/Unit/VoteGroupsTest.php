<?php

use App\Presenters\VoteGroups;

// Check C25 of .specs/features/app-skeleton/checks.md.

function entry(int $id, string $name, string $vote): array
{
    return ['deputyId' => $id, 'name' => $name, 'party' => 'P', 'uf' => 'SP', 'vote' => $vote];
}

test('orders vote groups and names', function () {
    $values = ['Zeta', '', 'Artigo 17', 'Beta', 'Obstrução', 'Abstenção', 'Não', 'Sim'];
    $groups = VoteGroups::of(array_map(fn (string $v, int $i) => entry($i, "Pessoa {$i}", $v), $values, array_keys($values)), secret: false);

    expect(array_column($groups, 'value'))->toBe(['Sim', 'Não', 'Abstenção', 'Obstrução', 'Artigo 17', '', 'Beta', 'Zeta']);

    $names = ['Zuleica', 'Érico', 'Abel', 'Ágata', 'Edu'];
    $one = VoteGroups::of(array_map(fn (string $n, int $i) => entry($i, $n, 'Sim'), $names, array_keys($names)), secret: false);

    expect($one)->toHaveCount(1)
        ->and(array_column($one[0]['entries'], 'name'))->toBe(['Abel', 'Ágata', 'Edu', 'Érico', 'Zuleica']);
});
