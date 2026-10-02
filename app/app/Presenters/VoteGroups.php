<?php

namespace App\Presenters;

use Collator;

/**
 * How each deputy voted, grouped by value (AC 19, 20): the known options in a fixed order, the
 * empty value after them, any other value alphabetically, names in pt-BR order inside a group.
 * A secret ballot records who voted and no vote, so it is one group.
 *
 * @phpstan-type Entry array{deputyId: int, name: string, party: string, uf: string, vote: string}
 * @phpstan-type Group array{value: string, label: string, entries: list<Entry>}
 */
final class VoteGroups
{
    public const ORDER = ['Sim', 'Não', 'Abstenção', 'Obstrução', 'Artigo 17', ''];

    private const LABELS = [
        'Artigo 17' => 'Art. 17 (presidente da sessão)',
        '' => 'Registro sem voto',
    ];

    /**
     * @param  list<Entry>  $entries
     * @return list<Group>
     */
    public static function of(array $entries, bool $secret): array
    {
        $collator = new Collator('pt_BR');
        $byName = fn (array $a, array $b) => $collator->compare($a['name'], $b['name']) ?: $a['deputyId'] <=> $b['deputyId'];

        if ($secret) {
            usort($entries, $byName);

            return $entries === [] ? [] : [['value' => '', 'label' => 'Deputados que votaram', 'entries' => $entries]];
        }

        $byValue = [];
        foreach ($entries as $entry) {
            $byValue[$entry['vote']][] = $entry;
        }
        $values = array_map(strval(...), array_keys($byValue));
        usort($values, function (string $a, string $b) use ($collator) {
            $ia = array_search($a, self::ORDER, true);
            $ib = array_search($b, self::ORDER, true);
            if ($ia !== false || $ib !== false) {
                return ($ia === false ? PHP_INT_MAX : $ia) <=> ($ib === false ? PHP_INT_MAX : $ib);
            }

            return $collator->compare($a, $b);
        });

        return array_map(function (string $value) use ($byValue, $byName) {
            $group = $byValue[$value];
            usort($group, $byName);

            return ['value' => $value, 'label' => self::LABELS[$value] ?? $value, 'entries' => $group];
        }, $values);
    }
}
