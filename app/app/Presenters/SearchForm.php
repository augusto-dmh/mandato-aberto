<?php

namespace App\Presenters;

use App\Models\Legislature;
use App\Models\Membership;
use App\Support\PublicUrl;
use Collator;
use Illuminate\Support\Collection;

/** The search form of the home and of `/busca/` (app-home AC 2, AC 20): its options and the values it shows. */
final class SearchForm
{
    public const HOUSES = ['camara' => 'Câmara dos Deputados', 'senado' => 'Senado Federal'];

    public const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

    /** @return Collection<int, Legislature> the stored legislatures some membership holds, newest first */
    public static function legislatures(): Collection
    {
        return Legislature::query()
            ->whereIn('number', Membership::query()->select('legislature_number'))
            ->orderByDesc('number')->get();
    }

    /** `58ª legislatura (2027–2031)`. */
    public static function label(Legislature $legislature): string
    {
        return "{$legislature->number}ª legislatura ({$legislature->starts_on->year}–{$legislature->ends_on->year})";
    }

    /** @return list<string> the parties of the memberships of legislature `$n`, in pt-BR order */
    public static function parties(int $n): array
    {
        $parties = Membership::query()->where('legislature_number', $n)->distinct()->pluck('party')->all();
        $collator = new Collator('pt_BR');
        usort($parties, fn (string $a, string $b) => $collator->compare($a, $b));

        return $parties;
    }

    /**
     * @param  Collection<int, Legislature>  $legislatures
     * @param  array{q: string, casa: ?string, uf: ?string, partido: ?string, legislatura: int, situacao: string}  $values
     * @return array<string, mixed>
     */
    public static function of(Collection $legislatures, array $values): array
    {
        return [
            'action' => PublicUrl::search(),
            'values' => $values,
            'houses' => array_map(fn ($v, $l) => ['value' => $v, 'label' => $l], array_keys(self::HOUSES), self::HOUSES),
            'ufs' => self::UFS,
            'parties' => self::parties($values['legislatura']),
            'legislatures' => $legislatures->map(fn (Legislature $l) => ['value' => $l->number, 'label' => self::label($l)])->values()->all(),
        ];
    }
}
