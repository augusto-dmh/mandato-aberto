<?php

namespace App\Http\Controllers;

use App\Models\ContractImport;
use App\Models\House;
use App\Models\Legislature;
use App\Models\Membership;
use App\Presenters\Dates;
use App\Presenters\HouseActivity;
use App\Presenters\Labels;
use App\Presenters\SearchForm;
use App\Support\PublicUrl;
use App\Support\SearchKey;
use Collator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/busca/`: the deputies and senators of one legislature, filtered, in alphabetical order only, 50 per
 * page, with no indicator (app-home S2). SQL narrows to the legislature and the house, UF and party;
 * the name match and the order run in PHP over that set (door 3).
 */
class SearchController extends Controller
{
    private const PER_PAGE = 50;

    public function show(Request $request): Response
    {
        $meta = [
            'title' => 'Buscar parlamentares',
            'description' => 'Busque deputados federais e senadores por nome, Casa, UF, partido e legislatura, em ordem alfabética.',
            'path' => PublicUrl::search(),
            'robots' => 'noindex, follow',
            'hydrate' => false,
        ];
        $current = ContractImport::query()->exists() ? HouseActivity::currentLegislature() : null;
        if ($current === null) {
            return Inertia::render('Search/Index', ['meta' => $meta, 'form' => null, 'sourcesLine' => Labels::sourcesLine()]);
        }

        // Each parameter outside its set counts as absent (AC 14); `null` here means "not given".
        $legislatures = SearchForm::legislatures();
        $given = fn (string $key) => is_string($value = $request->query($key)) ? $value : null;
        $n = $legislatures->firstWhere('number', ctype_digit((string) $given('legislatura')) ? (int) $given('legislatura') : null)?->number;
        $legislature = $n ?? $current;
        $casa = array_key_exists((string) $given('casa'), SearchForm::HOUSES) ? $given('casa') : null;
        $uf = in_array($given('uf'), SearchForm::UFS, true) ? $given('uf') : null;
        $partido = in_array($given('partido'), SearchForm::parties($legislature), true) ? $given('partido') : null;
        $situacao = in_array($given('situacao'), ['exercicio', 'todos'], true) ? $given('situacao') : null;
        $q = trim(mb_substr(trim((string) $given('q')), 0, 100));

        $rows = $this->rows($legislature, $current, $casa, $uf, $partido, $situacao !== 'todos', $q);
        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = $this->page($request->query('pagina'), $pages, $total);

        $params = ['q' => $q, 'casa' => $casa, 'uf' => $uf, 'partido' => $partido, 'legislatura' => $n, 'situacao' => $situacao];
        $ended = $legislature === $current ? null : Legislature::query()->find($legislature);

        return Inertia::render('Search/Index', [
            'meta' => $meta,
            'form' => SearchForm::of($legislatures, [
                'q' => $q, 'casa' => $casa, 'uf' => $uf, 'partido' => $partido, 'legislatura' => $legislature, 'situacao' => $situacao ?? 'exercicio',
            ]),
            'countLine' => $total === 1 ? '1 parlamentar, em ordem alfabética' : HouseActivity::number($total).' parlamentares, em ordem alfabética',
            'pastNote' => $ended === null ? null
                : "A {$legislature}ª legislatura terminou em ".Dates::br($ended->ends_on).'; a lista mostra todos que tiveram mandato nela.',
            'rows' => array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'pager' => $pages === 1 ? null : [
                'label' => "Página {$page} de {$pages}",
                'prev' => $page === 1 ? null : PublicUrl::search([...$params, 'pagina' => $page === 2 ? null : $page - 1]),
                'next' => $page === $pages ? null : PublicUrl::search([...$params, 'pagina' => $page + 1]),
            ],
            'clearUrl' => PublicUrl::search(),
            'sourcesLine' => Labels::sourcesLine(),
        ]);
    }

    /**
     * The matching memberships of legislature `$n`, ordered by name (pt-BR), then house, then source id (AC 15).
     *
     * @return list<array{house: string, id: string, name: string, houseName: string, party: string, uf: string, href: string}>
     */
    private function rows(int $n, int $current, ?string $casa, ?string $uf, ?string $partido, bool $inExercise, string $q): array
    {
        $query = Membership::query()->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('memberships.legislature_number', $n)
            ->when($casa !== null, fn (Builder $b) => $b->where('members.house', $casa))
            ->when($uf !== null, fn (Builder $b) => $b->where('memberships.uf', $uf))
            ->when($partido !== null, fn (Builder $b) => $b->where('memberships.party', $partido));

        // In exercise applies to the current legislature only (AC 12, AC 13): a period ending on or after
        // the Brasília day of the house's latest import.
        if ($inExercise && $n === $current) {
            $query->where(function (Builder $b) {
                $b->whereRaw('false');
                foreach (House::cases() as $house) {
                    $import = ContractImport::latestOf($house);
                    if ($import !== null) {
                        $b->orWhere(fn (Builder $h) => $h->where('members.house', $house)->whereExists(fn (QueryBuilder $p) => $p
                            ->from('exercise_periods')->whereColumn('exercise_periods.membership_id', 'memberships.id')
                            ->where('exercise_periods.ends_at', '>=', HouseActivity::brasiliaDay($import)->format('Y-m-d H:i:s'))));
                    }
                }
            });
        }

        $tokens = array_filter(explode(' ', SearchKey::of($q)), fn (string $t) => $t !== '');
        $rows = [];
        foreach ($query->get(['members.house', 'members.source_id', 'members.name', 'memberships.party', 'memberships.uf']) as $m) {
            $key = SearchKey::of((string) $m->getAttribute('name'));
            foreach ($tokens as $token) {
                if (! str_contains($key, $token)) {
                    continue 2;
                }
            }
            $house = House::from((string) $m->getAttribute('house'));
            $id = (string) $m->getAttribute('source_id');
            $rows[] = [
                'house' => $house->value,
                'id' => $id,
                'name' => (string) $m->getAttribute('name'),
                'houseName' => Labels::house($house),
                'party' => (string) $m->getAttribute('party'),
                'uf' => (string) $m->getAttribute('uf'),
                'href' => PublicUrl::member($house, $id, $n === $current ? null : $n),
            ];
        }

        $collator = new Collator('pt_BR');
        usort($rows, fn (array $a, array $b) => $collator->compare($a['name'], $b['name'])
            ?: strcmp($a['house'], $b['house'])
            ?: strnatcmp($a['id'], $b['id']));

        return $rows;
    }

    /** The page asked for; 404 when it is not an integer from 1 to `$pages` and there is something to page (AC 17). */
    private function page(mixed $raw, int $pages, int $total): int
    {
        if ($raw === null || $total === 0) {
            return 1;
        }
        abort_unless(is_string($raw) && ctype_digit($raw) && (int) $raw >= 1 && (int) $raw <= $pages, 404);

        return (int) $raw;
    }
}
