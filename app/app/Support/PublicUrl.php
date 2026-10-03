<?php

namespace App\Support;

use App\Models\House;

/** The only builder of the public paths of members and roll calls (plan door 4). */
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

    /** `/fotos/{sha256}.jpg`: content-addressed, so a changed photo gets a new URL (share-cards door 6). */
    public static function photo(string $sha256): string
    {
        return "/fotos/{$sha256}.jpg";
    }

    private static function house(House|string $house): House
    {
        return $house instanceof House ? $house : House::from($house);
    }
}
