<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Vote;
use App\Presenters\Labels;
use App\Support\PublicUrl;
use Inertia\Inertia;
use Inertia\Response;

/** A deputy's or a senator's page, one per mandate; the bare path shows the latest (plan S3, S4). */
class MemberController extends Controller
{
    public function deputy(string $id, ?string $n = null): Response
    {
        return $this->show(House::Camara, $id, $n);
    }

    public function senator(string $id, ?string $n = null): Response
    {
        return $this->show(House::Senado, $id, $n);
    }

    private function show(House $house, string $id, ?string $n): Response
    {
        $member = Member::query()->where('house', $house)->where('source_id', $id)->first();
        abort_if($member === null, 404);
        $mandates = $member->memberships()->with('legislature')->orderByDesc('legislature_number')->get();
        $membership = $n === null ? $mandates->first() : $mandates->firstWhere('legislature_number', (int) $n);
        abort_if($membership === null, 404);
        /** @var Membership $membership */
        $latest = $membership->is($mandates->first());
        $legislature = $membership->legislature_number;

        $note = fn (int $index, string $anchor) => [
            'index' => $index,
            'sourceUrl' => $member->source_url,
            'sourceLabel' => Labels::house($house),
            'methodUrl' => PublicUrl::methodology($anchor, absolute: true),
        ];
        $indicators = [];
        foreach ([
            ['participation', 'Participação em votações nominais do plenário', 'participacao'],
            ['government_alignment', 'Votos iguais à orientação do governo', 'alinhamento-governo'],
            ['party_alignment', 'Votos iguais à maioria do próprio partido', 'alinhamento-partido'],
        ] as $i => [$key, $label, $anchor]) {
            $indicators[] = [
                'label' => $label,
                'bases' => [
                    // `merit` first, named for what it holds; `all` second (plan, Assumptions: base order and names).
                    ['count' => $membership->{"{$key}_merit_count"}, 'total' => $membership->{"{$key}_merit_total"},
                        'label' => 'nas votações sobre propostas e emendas', 'note' => $note(2 * $i + 1, $anchor)],
                    ['count' => $membership->{"{$key}_all_count"}, 'total' => $membership->{"{$key}_all_total"},
                        'label' => 'em todas as votações nominais do plenário', 'note' => $note(2 * $i + 2, $anchor)],
                ],
            ];
        }

        $votes = $member->votes()
            ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
            ->with('rollCall.proposition')
            ->where('roll_calls.legislature_number', $legislature)
            ->where('roll_calls.organ', 'PLEN')
            ->orderBy('roll_calls.date')->orderBy('roll_calls.source_id')
            ->get(['votes.*'])
            ->map(fn (Vote $v) => [
                'rollCallId' => $v->rollCall->source_id,
                'date' => $v->rollCall->date->toDateString(),
                'title' => Labels::heading($v->rollCall),
                'position' => $v->position,
                // The Senate's official non-vote wording stays on the roll-call page (door 6).
                'official' => $house === House::Senado && $v->position === 'notVoting' ? null : $v->official,
                'href' => PublicUrl::rollCall($house, $v->rollCall->source_id),
            ]);

        $years = fn (Membership $m) => "{$m->legislature_number}ª legislatura ({$m->legislature->starts_on->year}–{$m->legislature->ends_on->year})";
        $where = "{$member->name} ({$membership->party}-{$membership->uf})";

        return Inertia::render('Members/Show', [
            'meta' => [
                'title' => "{$where} na {$legislature}ª legislatura",
                'description' => "Votos, participação em votações nominais e proposições de {$where} ".Labels::inHouse($house).', com dados oficiais e a base de cada número.',
                'path' => $latest ? PublicUrl::member($house, $member->source_id) : PublicUrl::member($house, $member->source_id, $legislature),
            ],
            'member' => [
                'id' => $member->source_id,
                'name' => $member->name,
                'house' => $house->value,
                'houseName' => Labels::house($house),
                'sourceUrl' => $member->source_url,
            ],
            'mandate' => [
                'legislature' => $legislature,
                'label' => $years($membership),
                'party' => $membership->party,
                'uf' => $membership->uf,
                'symbolicMerit' => $membership->symbolic_merit,
                'authoredCount' => $membership->authored_count,
                'firstSignerCount' => $membership->first_signer_count,
                'requirementsCount' => $membership->requirements_count,
            ],
            'legislatures' => $mandates->count() > 1 ? $mandates->map(fn (Membership $m) => [
                'number' => $m->legislature_number,
                'label' => $years($m),
                'href' => PublicUrl::member($house, $member->source_id, $m->legislature_number),
                'current' => $m->is($membership),
            ])->values()->all() : [],
            'indicators' => $indicators,
            'votes' => $votes,
            'classificationUrl' => PublicUrl::methodology('classificacao'),
            'proposicoesMethodUrl' => PublicUrl::methodology('proposicoes', absolute: true),
            'symbolicMethodUrl' => PublicUrl::methodology('votacoes-simbolicas', absolute: true),
            'scoreMethodUrl' => PublicUrl::methodology('participacao', absolute: true),
            'sources' => Labels::sources([$house]),
        ]);
    }
}
