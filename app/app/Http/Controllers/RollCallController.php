<?php

namespace App\Http\Controllers;

use App\Cards\Code;
use App\Cards\Payloads;
use App\Cards\Share;
use App\Models\House;
use App\Models\RollCall;
use App\Models\Vote;
use App\Presenters\Dates;
use App\Presenters\Labels;
use App\Presenters\VoteGroups;
use App\Support\PublicUrl;
use Inertia\Inertia;
use Inertia\Response;

/** A roll call of either house: how it was voted, what the rule says it decided, how each member voted (plan S5). */
class RollCallController extends Controller
{
    public function camara(string $id): Response
    {
        return $this->show(House::Camara, $id);
    }

    public function senado(string $id): Response
    {
        return $this->show(House::Senado, $id);
    }

    private function show(House $house, string $id): Response
    {
        $rollCall = RollCall::query()->with('proposition')->where('house', $house)->where('source_id', $id)->first();
        abort_if($rollCall === null, 404);

        $senate = $house === House::Senado;
        $date = Dates::br($rollCall->date);
        $heading = Labels::heading($rollCall);
        $organ = $rollCall->organ === 'PLEN' ? 'Plenário' : $rollCall->organ;
        $where = Labels::inHouse($house);
        $members = $senate ? 'senadores' : 'deputados';
        $tallied = $rollCall->tally_yes !== null;

        [$title, $description] = match ($rollCall->ballot) {
            'symbolic' => ["{$heading}: votação simbólica", "Votação simbólica de {$date} ({$organ}) {$where}, com o resultado oficial."],
            'secret' => ["{$heading}: votação secreta", "Votação secreta de {$date} ({$organ}) {$where}, com ".($tallied ? 'os totais oficiais e ' : '')."os {$members} que votaram."],
            default => ["{$heading}: como cada ".($senate ? 'senador' : 'deputado').' votou', "Votação nominal de {$date} ({$organ}) {$where}, com o voto de cada ".($senate ? 'senador' : 'deputado').' e o registro oficial.'],
        };

        $entries = $rollCall->votes()->with('member')->get()->map(fn (Vote $v) => [
            'memberId' => $v->member->source_id,
            'name' => $v->member->name,
            'party' => $v->party,
            'uf' => $v->member->uf,
            'href' => PublicUrl::member($house, $v->member->source_id),
            'position' => $v->position,
            'official' => $v->official,
        ])->values()->all();

        // The card of this roll call, computed and never stored here (share-cards door 8).
        $payload = Payloads::rollCall($house, $rollCall);
        $code = Code::of($payload);

        return Inertia::render('RollCalls/Show', [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'path' => PublicUrl::rollCall($house, $rollCall->source_id),
                'image' => Share::image($payload, $code),
            ],
            'card' => Share::card($payload, $code),
            'rollCall' => [
                'id' => $rollCall->source_id,
                'house' => $house->value,
                'date' => $rollCall->date->toDateString(),
                'organLabel' => $organ,
                'title' => $heading,
                'summary' => $rollCall->proposition?->summary,
                'description' => $rollCall->description,
                'ballot' => $rollCall->ballot,
                'classification' => Labels::BALLOTS[$rollCall->ballot].' · '.Labels::KINDS[$rollCall->kind],
                'rule' => $rollCall->kind === 'unclassified' || $rollCall->kind_rule === null
                    ? ['label' => 'como as votações são classificadas', 'href' => PublicUrl::methodology('classificacao')]
                    : ['label' => "regra {$rollCall->kind_rule}", 'href' => PublicUrl::methodology(Labels::ruleAnchor($rollCall->kind_rule))],
                'resultLabel' => match ($rollCall->approved) {
                    true => 'Aprovada',
                    false => 'Rejeitada',
                    null => 'Resultado não informado',
                },
                'tallies' => $tallied ? ['yes' => $rollCall->tally_yes, 'no' => $rollCall->tally_no, 'others' => $rollCall->tally_others] : null,
                'governmentOrientation' => $rollCall->government_orientation === null ? null : Labels::ORIENTATIONS[$rollCall->government_orientation],
                'sourceUrl' => $rollCall->source_url,
                'sourceLabel' => Labels::house($house).', dados abertos da votação',
                'sourceLink' => $senate ? 'Dados abertos do Senado sobre esta votação' : 'Dados abertos da Câmara sobre esta votação',
                'membersHeading' => $senate ? 'Como cada senador votou' : 'Como cada deputado votou',
            ],
            'groups' => VoteGroups::of($entries, $house),
            'sources' => Labels::sources([$house]),
        ]);
    }
}
