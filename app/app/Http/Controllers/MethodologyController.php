<?php

namespace App\Http\Controllers;

use App\Models\ClassificationRule;
use App\Models\ContractImport;
use App\Models\House;
use App\Presenters\Dates;
use App\Presenters\Labels;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/** `/metodologia/`: each v3 number explained, and the rules and coverage of each house's latest import (plan S6). */
class MethodologyController extends Controller
{
    public function show(): Response
    {
        $houses = [];
        foreach (House::cases() as $house) {
            $import = ContractImport::latestOf($house);
            $houses[] = [
                'house' => $house->value,
                'name' => Labels::house($house),
                'ofName' => Labels::ofHouse($house),
                'imported' => $import !== null,
                'version' => $import?->classification_version,
                'sourceUrl' => Labels::openData($house),
                'collectedAt' => $import?->generated_at->utc()->format('Y-m-d\\TH:i:s\\Z'),
                'rules' => $import === null ? [] : ClassificationRule::query()->where('house', $house)->orderBy('position')->get()
                    ->map(fn (ClassificationRule $r) => [
                        'id' => $r->rule_id,
                        'anchor' => Labels::ruleAnchor($r->rule_id),
                        'kind' => Labels::KINDS[$r->kind],
                        'field' => $r->field,
                        'pattern' => $r->pattern,
                        'description' => $r->description,
                    ])->all(),
                'coverage' => array_map(fn (array $c) => [
                    'legislature' => "{$c['legislature']}ª",
                    'through' => $c['through'] === null ? null : Dates::br(CarbonImmutable::parse($c['through'])),
                    'nominal' => $c['rollCalls']['nominal'],
                    'secret' => $c['rollCalls']['secret'],
                    'symbolic' => $c['rollCalls']['symbolic'],
                    'unclassified' => $c['unclassified'],
                ], $import->coverage ?? []),
            ];
        }

        return Inertia::render('Methodology/Show', [
            'meta' => [
                'title' => 'Metodologia',
                'description' => 'Como cada número do Mandato Aberto é contado: as duas bases, os indicadores, a classificação das votações e a cobertura dos dados.',
                'path' => '/metodologia/',
            ],
            'houses' => $houses,
            'coverageMethodUrl' => PublicUrl::methodology('tipos-de-votacao', absolute: true),
            'sources' => Labels::sources(House::cases()),
        ]);
    }
}
