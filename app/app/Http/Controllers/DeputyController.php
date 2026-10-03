<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Member;
use App\Models\Vote;
use Inertia\Inertia;
use Inertia\Response;

class DeputyController extends Controller
{
    public function show(string $id): Response
    {
        $member = Member::query()->where('house', House::Camara)->where('source_id', $id)->first();
        $membership = $member?->memberships()->orderByDesc('legislature_number')->first();
        abort_if($member === null || $membership === null, 404);

        $method = (string) config('mandato.method_url');
        $note = fn (int $index, string $anchor) => ['index' => $index, 'sourceUrl' => $member->source_url, 'methodUrl' => "{$method}#{$anchor}"];
        $indicator = fn (string $key, string $label, array $note) => [
            'count' => $membership->{"{$key}_count"},
            'total' => $membership->{"{$key}_total"},
            'label' => $label,
            'note' => $note,
        ];

        $votes = $member->votes()
            ->join('roll_calls', 'roll_calls.id', '=', 'votes.roll_call_id')
            ->leftJoin('propositions', 'propositions.id', '=', 'roll_calls.proposition_id')
            ->where('roll_calls.legislature_number', $membership->legislature_number)
            ->orderBy('roll_calls.date')->orderBy('roll_calls.source_id')
            ->get(['votes.vote', 'roll_calls.source_id', 'roll_calls.date', 'roll_calls.secret', 'roll_calls.description', 'propositions.title'])
            ->map(fn (Vote $v) => [
                'rollCallId' => $v->getAttribute('source_id'),
                'date' => substr((string) $v->getAttribute('date'), 0, 10),
                'title' => $v->getAttribute('title') ?? $v->getAttribute('description'),
                'vote' => $v->vote,
                'secret' => (bool) $v->getAttribute('secret'),
            ]);

        $where = "{$member->name} ({$member->party}-{$member->uf})";

        return Inertia::render('Deputies/Show', [
            'meta' => [
                'title' => "{$where} na {$membership->legislature_number}ª legislatura",
                'description' => "Votos, participação em votações nominais e proposições de {$where} na Câmara dos Deputados, com dados oficiais e a base de cada número.",
                'path' => "/deputados/{$member->source_id}/",
            ],
            'deputy' => [
                'id' => $member->source_id,
                'name' => $member->name,
                'party' => $member->party,
                'uf' => $member->uf,
                'sourceUrl' => $member->source_url,
                'authoredCount' => $membership->authored_count,
                'firstSignerCount' => $membership->first_signer_count,
                'requirementsCount' => $membership->requirements_count,
            ],
            'indicators' => [
                $indicator('participation', 'Participação em votações nominais do plenário', $note(1, 'participacao')),
                $indicator('government_alignment', 'Votos iguais à orientação do governo', $note(2, 'alinhamento-governo')),
                $indicator('party_alignment', 'Votos iguais à maioria do próprio partido', $note(3, 'alinhamento-partido')),
            ],
            'votes' => $votes,
            'methodBase' => $method,
        ]);
    }
}
