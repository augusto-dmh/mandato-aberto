<?php

namespace App\Presenters;

use App\Models\House;
use Collator;

/**
 * How each member voted, grouped by position (AC 43): the seven positions in a fixed order, each
 * headed in the house's words, names in pt-BR order inside a group.
 *
 * @phpstan-type Entry array{memberId: string, name: string, party: string, uf: string, href: string, position: string, official: string}
 * @phpstan-type Group array{position: string, label: string, entries: list<Entry>}
 */
final class VoteGroups
{
    public const ORDER = ['yes', 'no', 'abstention', 'obstruction', 'presiding', 'secret', 'notVoting'];

    /**
     * @param  list<Entry>  $entries
     * @return list<Group>
     */
    public static function of(array $entries, House $house): array
    {
        $collator = new Collator('pt_BR');
        $byName = fn (array $a, array $b) => $collator->compare($a['name'], $b['name']) ?: strcmp($a['memberId'], $b['memberId']);

        $byPosition = [];
        foreach ($entries as $entry) {
            $byPosition[$entry['position']][] = $entry;
        }

        $groups = [];
        foreach (self::ORDER as $position) {
            if (! isset($byPosition[$position])) {
                continue;
            }
            $group = $byPosition[$position];
            usort($group, $byName);
            $groups[] = ['position' => $position, 'label' => self::label($position, $house), 'entries' => $group];
        }

        return $groups;
    }

    public static function label(string $position, House $house): string
    {
        $senate = $house === House::Senado;

        return match ($position) {
            'yes' => 'Sim',
            'no' => 'Não',
            'abstention' => 'Abstenção',
            'obstruction' => 'Obstrução',
            // The house's own presiding label (door 5), as `positionCase` in design/components/vote.js writes it.
            'presiding' => $senate ? 'Presidente da sessão (art. 51 RISF)' : 'Art. 17 (presidente da sessão)',
            'secret' => $senate ? 'Senadores que votaram' : 'Deputados que votaram',
            default => 'Sem voto registrado',
        };
    }
}
