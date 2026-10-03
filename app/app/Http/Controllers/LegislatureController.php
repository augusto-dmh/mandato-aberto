<?php

namespace App\Http\Controllers;

use App\Models\ContractImport;
use App\Models\House;
use App\Models\Legislature;
use App\Models\RollCall;
use App\Presenters\Dates;
use App\Presenters\HouseActivity;
use App\Presenters\Labels;
use App\Presenters\SearchForm;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/legislaturas/{n}/`: what each house did in a legislature, as counts with notes, a calendar by
 * month and the latest roll calls. It names no person (app-home S3, AD-019).
 */
class LegislatureController extends Controller
{
    private const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    public function show(string $n): Response
    {
        $legislature = Legislature::query()->find((int) $n);
        abort_if($legislature === null, 404);
        /** @var Legislature $legislature */
        $number = $legislature->number;

        $houses = [];
        $index = 1;
        foreach (House::cases() as $house) {
            $import = ContractImport::latestOf($house);
            $section = ['house' => $house->value, 'name' => Labels::house($house)];
            if ($import === null || HouseActivity::coverage($import, $number) === null) {
                $houses[] = [...$section, 'missing' => 'Ainda não há dados '.Labels::ofHouse($house)." para a {$number}ª legislatura."];

                continue;
            }
            $section['counts'] = HouseActivity::counts($house, $number, $import, ['nominal', 'secret', 'symbolic', 'members', 'propositions'], $index);
            $rollCalls = HouseActivity::plenary($house, $number)->whereIn('ballot', ['nominal', 'secret'])->with('proposition')->get();
            if ($rollCalls->isEmpty()) {
                $section['none'] = 'Nenhuma votação nominal ou secreta no plenário até '.Dates::br(HouseActivity::brasiliaDay($import)).'.';
            } else {
                [$section['years'], $section['months']] = $this->calendar($legislature, $rollCalls);
                $section['recent'] = $this->recent($house, $rollCalls);
            }
            $houses[] = $section;
        }

        $all = Legislature::query()->orderByDesc('number')->get();
        $title = "{$number}ª legislatura: votações e proposições na Câmara e no Senado";

        return Inertia::render('Legislatures/Show', [
            'meta' => [
                'title' => $title,
                'description' => "Quantas votações nominais, secretas e simbólicas e quantas proposições houve na {$number}ª legislatura, por Casa, com dados oficiais e a fonte de cada número.",
                'path' => PublicUrl::legislature($number),
                'hydrate' => false,
            ],
            'legislature' => [
                'number' => $number,
                'dates' => 'De '.Dates::br($legislature->starts_on).' a '.Dates::br($legislature->ends_on),
            ],
            'legislatures' => $all->count() > 1 ? $all->map(fn (Legislature $l) => [
                'label' => SearchForm::label($l),
                'href' => PublicUrl::legislature($l->number),
                'current' => $l->number === $number,
            ])->values()->all() : [],
            'houses' => $houses,
            'sourcesLine' => Labels::sourcesLine(),
        ]);
    }

    /**
     * One cell per month from the legislature's first to the month of the latest roll call, by year, and
     * the same months as table rows with the days that had a roll call (AC 25, AC 26).
     *
     * @param  Collection<int, RollCall>  $rollCalls
     * @return array{list<array{year: int, cells: list<array{month: string, count: int, height: string}>}>, list<array{label: string, count: int, days: int}>}
     */
    private function calendar(Legislature $legislature, Collection $rollCalls): array
    {
        $byMonth = $rollCalls->groupBy(fn (RollCall $r) => $r->date->format('Y-m'));
        $highest = $byMonth->map->count()->max();
        $last = $byMonth->keys()->max();

        $years = [];
        $months = [];
        for ($month = CarbonImmutable::parse($legislature->starts_on)->startOfMonth(); $month->format('Y-m') <= $last; $month = $month->addMonth()) {
            $calls = $byMonth->get($month->format('Y-m'), collect());
            $count = $calls->count();
            $height = rtrim(rtrim(number_format(round($count * 100 / $highest, 1), 1, '.', ''), '0'), '.');
            $name = self::MONTHS[$month->month - 1];
            $years[$month->year] ??= ['year' => $month->year, 'cells' => []];
            $years[$month->year]['cells'][] = ['month' => mb_substr($name, 0, 3), 'count' => $count, 'height' => "{$height}%"];
            $months[] = [
                'label' => "{$name} de {$month->year}",
                'count' => $count,
                'days' => $calls->map(fn (RollCall $r) => $r->date->toDateString())->unique()->count(),
            ];
        }

        return [array_values($years), $months];
    }

    /**
     * The 10 latest, by date and then id, newest first (AC 27).
     *
     * @param  Collection<int, RollCall>  $rollCalls
     * @return list<array<string, string>>
     */
    private function recent(House $house, Collection $rollCalls): array
    {
        return $rollCalls
            ->sort(fn (RollCall $a, RollCall $b) => strcmp($b->date->toDateString(), $a->date->toDateString()) ?: strnatcmp($b->source_id, $a->source_id))
            ->take(10)
            ->map(fn (RollCall $r) => [
                'date' => Dates::br($r->date),
                'title' => Labels::heading($r),
                'classification' => Labels::BALLOTS[$r->ballot].' · '.Labels::KINDS[$r->kind],
                'result' => match ($r->approved) {
                    true => 'Aprovada',
                    false => 'Rejeitada',
                    null => 'Resultado não informado',
                },
                'href' => PublicUrl::rollCall($house, $r->source_id),
            ])->values()->all();
    }
}
