<?php

namespace App\Support;

use App\Models\House;

/** The only builder of the public paths of members and roll calls (plan door 4), the overview and search. */
final class PublicUrl
{
    /** `/deputados/{id}/` or `/senadores/{id}/`, with `legislatura/{n}/` when a legislature is given. */
    public static function member(House|string $house, string $id, ?int $legislature = null): string
    {
        $base = self::house($house) === House::Senado ? "/senadores/{$id}/" : "/deputados/{$id}/";

        return $legislature === null ? $base : "{$base}legislatura/{$legislature}/";
    }

    /** `/votacoes/{id}/` for the Câmara, `/senado/votacoes/{id}/` for the Senate. */
    public static function rollCall(House|string $house, string $id): string
    {
        return self::house($house) === House::Senado ? "/senado/votacoes/{$id}/" : "/votacoes/{$id}/";
    }

    /** `/metodologia/`, with `#{anchor}` when one is given; `$absolute` prefixes `APP_URL`, as every `methodUrl` is. */
    public static function methodology(?string $anchor = null, bool $absolute = false): string
    {
        $path = '/metodologia/'.($anchor === null ? '' : "#{$anchor}");

        return $absolute ? rtrim((string) config('app.url'), '/').$path : $path;
    }

    /** `/legislaturas/{n}/`, the overview of a legislature (app-home door 1). */
    public static function legislature(int $n): string
    {
        return "/legislaturas/{$n}/";
    }

    /**
     * `/busca/`, with the given parameters in door 1's order and the empty ones left out.
     *
     * @param  array<string, string|int|null>  $params
     */
    public static function search(array $params = []): string
    {
        $query = [];
        foreach (['q', 'casa', 'uf', 'partido', 'legislatura', 'situacao', 'pagina'] as $key) {
            if (($params[$key] ?? null) !== null && $params[$key] !== '') {
                $query[$key] = $params[$key];
            }
        }

        return '/busca/'.($query === [] ? '' : '?'.http_build_query($query, encoding_type: PHP_QUERY_RFC3986));
    }

    private static function house(House|string $house): House
    {
        return $house instanceof House ? $house : House::from($house);
    }
}
