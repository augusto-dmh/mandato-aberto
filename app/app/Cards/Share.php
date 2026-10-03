<?php

namespace App\Cards;

use App\Models\House;
use App\Presenters\Labels;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;

/**
 * What a page says about its card (share-cards S6): the `og:image` of door 7 with the AC 41 alt, and
 * the three format links and the code of AC 42. Computed from the payload, so it writes nothing (AC 43).
 */
final class Share
{
    /** The short labels of the alt text, in the order of `Payloads::FIGURES`. */
    private const ALT_FIGURES = ['participação', 'votos iguais à orientação do governo', 'votos iguais à maioria do partido'];

    /** @param  array<string, mixed>  $payload */
    public static function subjectPath(array $payload): string
    {
        return $payload['kind'] === 'member'
            ? PublicUrl::member((string) $payload['house'], (string) $payload['sourceId'], (int) $payload['legislature'])
            : PublicUrl::rollCall((string) $payload['house'], (string) $payload['sourceId']);
    }

    /**
     * The `meta.image` of door 7: the `1200x630` card of this code, absolute.
     *
     * @param  array<string, mixed>  $payload
     * @return array{url: string, width: int, height: int, alt: string}
     */
    public static function image(array $payload, string $code): array
    {
        return [
            'url' => rtrim((string) config('app.url'), '/').PublicUrl::card(self::subjectPath($payload), $code, '1200x630'),
            'width' => 1200,
            'height' => 630,
            'alt' => self::alt($payload, $code),
        ];
    }

    /**
     * The `card` prop of the member and roll-call pages: the code and each format's image path.
     *
     * @param  array<string, mixed>  $payload
     * @return array{code: string, verifyUrl: string, formats: list<array{label: string, url: string}>}
     */
    public static function card(array $payload, string $code): array
    {
        $path = self::subjectPath($payload);

        return [
            'code' => $code,
            'verifyUrl' => PublicUrl::verify($code),
            'formats' => [
                ['label' => 'Horizontal, 1200 × 630', 'url' => PublicUrl::card($path, $code, '1200x630')],
                ['label' => 'Feed, 1080 × 1350', 'url' => PublicUrl::card($path, $code, '1080x1350')],
                ['label' => 'Stories, 1080 × 1920', 'url' => PublicUrl::card($path, $code, '1080x1920')],
            ],
        ];
    }

    /**
     * AC 41: the card's text as one description, for `og:image:alt`, `twitter:image:alt` and the verification page.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function alt(array $payload, string $code): string
    {
        $house = Labels::house(House::from((string) $payload['house']));
        $tail = 'Dados de '.self::dataDate($payload).". Código {$code}.";

        if ($payload['kind'] === 'member') {
            $figures = [];
            foreach ($payload['figures'] as $i => $f) {
                $figures[] = $f['total'] > 0
                    ? self::ALT_FIGURES[$i].' em '.self::number($f['count']).' de '.self::number($f['total'])
                    : self::ALT_FIGURES[$i].': sem base de cálculo';
            }

            return "Card do Mandato Aberto: {$payload['name']} ({$payload['party']}-{$payload['uf']}), {$house}, {$payload['legislature']}ª legislatura. "
                .'Nas votações sobre propostas e emendas: '.implode('; ', $figures).". {$tail}";
        }

        $date = CarbonImmutable::parse((string) $payload['date'])->format('d/m/Y');

        $tallies = self::tallies($payload);
        $count = $tallies === null ? self::noTallies($payload) : implode(', ', $tallies).'.';

        return "Card do Mandato Aberto: {$payload['heading']}, {$house}, {$date}. ".self::result($payload).". {$count} {$tail}";
    }

    /**
     * `Aprovada`, `Rejeitada` or `Resultado não informado` (AC 27).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function result(array $payload): string
    {
        return match ($payload['approved']) {
            1 => 'Aprovada',
            0 => 'Rejeitada',
            default => 'Resultado não informado',
        };
    }

    /**
     * The tallies as the card writes them (`40 Sim`, `20 Não`, `1 outros votos`), or null when the card has none.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>|null
     */
    public static function tallies(array $payload): ?array
    {
        $t = $payload['tallies'];
        if ($payload['ballot'] === 'symbolic' || $t === null) {
            return null;
        }

        return [self::number($t['yes']).' Sim', self::number($t['no']).' Não', self::number($t['others']).' outros votos'];
    }

    /**
     * The AC 27 sentence a roll-call card writes in place of its tallies.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function noTallies(array $payload): string
    {
        return $payload['ballot'] === 'symbolic'
            ? 'Votação simbólica: não há registro do voto de cada parlamentar nem placar.'
            : 'Placar não publicado pela Casa.';
    }

    /**
     * The Brasília date of the payload's data, `DD/MM/AAAA` (AC 28).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function dataDate(array $payload): string
    {
        return CarbonImmutable::parse((string) $payload['generatedAt'])->setTimezone('America/Sao_Paulo')->format('d/m/Y');
    }

    private static function number(mixed $n): string
    {
        return number_format((int) $n, 0, ',', '.');
    }
}
