<?php

namespace App\Http\Controllers;

use App\Models\ContractImport;
use App\Models\House;
use App\Presenters\HouseActivity;
use App\Presenters\Labels;
use App\Presenters\SearchForm;
use App\Support\PublicUrl;
use Inertia\Inertia;
use Inertia\Response;

/** `/`: what the site is, the search form and the current legislature's headline counts (app-home S1). */
class HomeController extends Controller
{
    public const TITLE = 'O que cada parlamentar federal fez no mandato';

    public const LEDE = 'O Mandato Aberto mostra como cada parlamentar votou e o que apresentou na Câmara dos Deputados e no Senado Federal, com dados abertos das duas Casas e a fonte e o método de cada número.';

    public function show(): Response
    {
        $current = ContractImport::query()->exists() ? HouseActivity::currentLegislature() : null;

        $houses = [];
        $index = 1;
        if ($current !== null) {
            foreach (House::cases() as $house) {
                $import = ContractImport::latestOf($house);
                if ($import !== null && HouseActivity::coverage($import, $current) !== null) {
                    $houses[] = [
                        'house' => $house->value,
                        'name' => Labels::house($house),
                        'counts' => HouseActivity::counts($house, $current, $import, ['nominal', 'members'], $index),
                    ];
                }
            }
        }

        return Inertia::render('Home/Index', [
            'meta' => ['title' => self::TITLE, 'description' => self::LEDE, 'path' => '/', 'hydrate' => false],
            'lede' => self::LEDE,
            'form' => $current === null ? null : SearchForm::of(SearchForm::legislatures(), [
                'q' => '', 'casa' => null, 'uf' => null, 'partido' => null, 'legislatura' => $current, 'situacao' => 'exercicio',
            ]),
            'houses' => $houses,
            'overview' => $houses === [] ? null : ['label' => "Visão geral da {$current}ª legislatura", 'href' => PublicUrl::legislature((int) $current)],
            'sourcesLine' => Labels::sourcesLine(),
        ]);
    }
}
