<?php

namespace App\Cards;

use App\Media\Photos;
use App\Models\ContractImport;
use App\Models\House;
use App\Models\Member;
use App\Models\Membership;
use App\Models\RollCall;
use App\Models\Vote;
use App\Presenters\Labels;

/**
 * What a card shows, as the payload its code is computed from (share-cards door 5): every displayed
 * value and nothing else, the same for the page, the image and the verification page.
 */
final class Payloads
{
    public const FIGURES = [
        ['participation', 'Participação em votações nominais do plenário'],
        ['government_alignment', 'Votos iguais à orientação do governo'],
        ['party_alignment', 'Votos iguais à maioria do próprio partido'],
    ];

    /** @return array<string, mixed> */
    public static function member(House $house, Member $member, Membership $membership): array
    {
        $legislature = $membership->legislature_number;
        $votes = $member->votes()
            ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
            ->with('rollCall')
            ->where('roll_calls.legislature_number', $legislature)
            ->where('roll_calls.organ', 'PLEN')
            ->orderBy('roll_calls.date')->orderBy('roll_calls.source_id')
            ->get(['votes.*'])
            ->map(fn (Vote $v) => ['rollCallId' => $v->rollCall->source_id, 'date' => $v->rollCall->date->toDateString(), 'position' => $v->position])
            ->all();

        return [
            ...self::common($house, 'member', $member->source_id, $legislature),
            'photoSha256' => Photos::currentOf($house, $member->source_id)?->sha256,
            'name' => $member->name,
            'party' => $membership->party,
            'uf' => $membership->uf,
            'figures' => array_map(fn (array $f) => [
                'label' => $f[1],
                'count' => (int) $membership->{"{$f[0]}_merit_count"},
                'total' => (int) $membership->{"{$f[0]}_merit_total"},
            ], self::FIGURES),
            'votes' => $votes,
        ];
    }

    /** @return array<string, mixed> */
    public static function rollCall(House $house, RollCall $rollCall): array
    {
        return [
            ...self::common($house, 'roll_call', $rollCall->source_id, null),
            'photoSha256' => null,
            'heading' => Labels::heading($rollCall),
            'date' => $rollCall->date->toDateString(),
            'ballot' => $rollCall->ballot,
            'rollCallKind' => $rollCall->kind,
            'approved' => $rollCall->approved === null ? null : (int) $rollCall->approved,
            'tallies' => $rollCall->tally_yes === null ? null
                : ['yes' => $rollCall->tally_yes, 'no' => (int) $rollCall->tally_no, 'others' => (int) $rollCall->tally_others],
        ];
    }

    /** @return array<string, mixed> */
    private static function common(House $house, string $kind, string $sourceId, ?int $legislature): array
    {
        $import = ContractImport::latestOf($house);

        return [
            'template' => (int) config('mandato.card_template'),
            'kind' => $kind,
            'house' => $house->value,
            'sourceId' => $sourceId,
            'legislature' => $legislature,
            'generatedAt' => $import?->generated_at->utc()->format('Y-m-d\\TH:i:s\\Z'),
        ];
    }
}
