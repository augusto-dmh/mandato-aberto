<?php

namespace App\Presenters;

use App\Models\ContractImport;
use App\Models\House;
use App\Models\Membership;
use App\Models\RollCall;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What a house did in a legislature, counted from the stored rows (app-home S1, S3): plenary roll
 * calls by ballot, memberships and propositions presented, each with its note.
 *
 * @phpstan-type Count array{value: string|null, label: string, note: array<string, mixed>|null}
 */
final class HouseActivity
{
    public const SOURCE_URLS = [
        'camara' => 'https://dadosabertos.camara.leg.br/',
        'senado' => 'https://legis.senado.leg.br/dadosabertos/',
    ];

    /** The proposition types counted as presented (methodology `#proposicoes`). */
    public const AUTHORED_TYPES = [
        'camara' => ['PL', 'PLP', 'PEC', 'PDL', 'PRC'],
        'senado' => ['PL', 'PLP', 'PEC', 'PDL', 'PRS'],
    ];

    /** @var array<string, array{string, string, string}> singular, plural, method anchor */
    private const LABELS = [
        'nominal' => ['votação nominal no plenário', 'votações nominais no plenário', 'tipos-de-votacao'],
        'secret' => ['votação secreta no plenário', 'votações secretas no plenário', 'tipos-de-votacao'],
        'symbolic' => ['votação simbólica no plenário', 'votações simbólicas no plenário', 'tipos-de-votacao'],
        'members' => ['parlamentar com mandato na legislatura', 'parlamentares com mandato na legislatura', 'cobertura'],
        'propositions' => ['proposição apresentada por parlamentares', 'proposições apresentadas por parlamentares', 'proposicoes'],
    ];

    /** The highest legislature any stored membership holds, either house; null with none. */
    public static function currentLegislature(): ?int
    {
        $n = Membership::query()->max('legislature_number');

        return $n === null ? null : (int) $n;
    }

    /**
     * Plenary roll calls of a house: the Câmara's with organ `PLEN`, every Senate roll call.
     *
     * @return Builder<RollCall>
     */
    public static function plenary(House $house, int $legislature): Builder
    {
        return RollCall::query()->where('house', $house)->where('legislature_number', $legislature)
            ->when($house === House::Camara, fn (Builder $q) => $q->where('organ', 'PLEN'));
    }

    /**
     * The coverage row the import lists for legislature `$n`, or null when it does not list it.
     *
     * @return array{legislature: int, through: string|null, rollCalls: array{nominal: int, secret: int, symbolic: int|null}, unclassified: int, members: int}|null
     */
    public static function coverage(?ContractImport $import, int $n): ?array
    {
        foreach ($import->coverage ?? [] as $row) {
            if ($row['legislature'] === $n) {
                return $row;
            }
        }

        return null;
    }

    /** The calendar day in Brasília (UTC-3, no daylight saving) of an import's `generatedAt`. */
    public static function brasiliaDay(ContractImport $import): CarbonImmutable
    {
        return $import->generated_at->utc()->subHours(3)->startOfDay();
    }

    /** `1.500`: a count as the site writes it. */
    public static function number(int $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    /**
     * The counts named in `$keys`, each with its `SourceNote`, numbered from `$index`.
     *
     * @param  list<string>  $keys
     * @return list<Count>
     */
    public static function counts(House $house, int $n, ContractImport $import, array $keys, int &$index): array
    {
        $plenary = self::plenary($house, $n)->selectRaw('ballot, count(*) as n')->groupBy('ballot')->pluck('n', 'ballot');
        $coverage = self::coverage($import, $n);
        $symbolicPublished = $coverage === null || $coverage['rollCalls']['symbolic'] !== null;

        $counts = [];
        foreach ($keys as $key) {
            if ($key === 'symbolic' && ! $symbolicPublished) {
                $counts[] = ['value' => null, 'label' => 'Votações simbólicas no plenário: não publicadas pela Casa.', 'note' => null];

                continue;
            }
            $value = match ($key) {
                'members' => Membership::query()->join('members', 'members.id', '=', 'memberships.member_id')
                    ->where('members.house', $house)->where('memberships.legislature_number', $n)->count(),
                'propositions' => DB::table('propositions')
                    ->join('authorships', 'authorships.proposition_id', '=', 'propositions.id')
                    ->join('memberships', 'memberships.id', '=', 'authorships.membership_id')
                    ->join('members', 'members.id', '=', 'memberships.member_id')
                    ->where('members.house', $house->value)->where('memberships.legislature_number', $n)
                    ->whereIn('propositions.type', self::AUTHORED_TYPES[$house->value])
                    ->distinct()->count('propositions.id'),
                default => (int) ($plenary[$key] ?? 0),
            };
            [$one, $many, $anchor] = self::LABELS[$key];
            $label = $value === 1 ? $one : $many;
            if ($key === 'propositions') {
                $label .= ' ('.implode(', ', array_slice(self::AUTHORED_TYPES[$house->value], 0, -1)).' e '.self::AUTHORED_TYPES[$house->value][4].')';
            }
            $counts[] = [
                'value' => self::number($value),
                'label' => $label,
                'note' => [
                    'index' => $index++,
                    'sourceUrl' => self::SOURCE_URLS[$house->value],
                    'sourceLabel' => Labels::house($house),
                    'methodUrl' => PublicUrl::methodology($anchor, absolute: true),
                    'collectedAt' => $import->generated_at->utc()->format('Y-m-d\\TH:i:s\\Z'),
                ],
            ];
        }

        return $counts;
    }
}
