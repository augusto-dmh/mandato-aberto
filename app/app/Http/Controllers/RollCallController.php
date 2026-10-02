<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\RollCall;
use App\Models\Vote;
use App\Presenters\Dates;
use App\Presenters\VoteGroups;
use Inertia\Inertia;
use Inertia\Response;

class RollCallController extends Controller
{
    public function show(string $id): Response
    {
        $rollCall = RollCall::query()->with('proposition')->where('house', House::Camara)->where('source_id', $id)->first();
        abort_if($rollCall === null, 404);

        $date = Dates::br($rollCall->date);
        $heading = $rollCall->proposition->title ?? "Votação nominal de {$date}";
        $organ = $rollCall->organ === 'PLEN' ? 'Plenário' : $rollCall->organ;

        $entries = $rollCall->votes()->with('member')->get()->map(fn (Vote $v) => [
            'deputyId' => (int) $v->member->source_id,
            'name' => $v->member->name,
            'party' => $v->party,
            'uf' => $v->member->uf,
            'vote' => $v->vote,
        ])->values()->all();

        return Inertia::render('RollCalls/Show', [
            'meta' => [
                'title' => $rollCall->secret ? "{$heading}: votação secreta" : "{$heading}: como cada deputado votou",
                'description' => $rollCall->secret
                    ? "Votação secreta de {$date} ({$organ}) na Câmara dos Deputados, com os totais oficiais e os deputados que votaram."
                    : "Votação nominal de {$date} ({$organ}) na Câmara dos Deputados, com o voto de cada deputado e o registro oficial.",
                'path' => "/votacoes/{$rollCall->source_id}/",
            ],
            'rollCall' => [
                'id' => $rollCall->source_id,
                'date' => $rollCall->date->toDateString(),
                'organLabel' => $organ,
                'title' => $heading,
                'summary' => $rollCall->proposition?->summary,
                'description' => $rollCall->description,
                'resultLabel' => match ($rollCall->approved) {
                    true => 'Aprovada',
                    false => 'Rejeitada',
                    null => 'Resultado não informado',
                },
                'tallies' => ['yes' => $rollCall->tally_yes, 'no' => $rollCall->tally_no, 'others' => $rollCall->tally_others],
                'secret' => $rollCall->secret,
                'governmentOrientation' => $rollCall->government_orientation,
                'sourceUrl' => $rollCall->source_url,
            ],
            'groups' => VoteGroups::of($entries, $rollCall->secret),
        ]);
    }
}
