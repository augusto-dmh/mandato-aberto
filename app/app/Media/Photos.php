<?php

namespace App\Media;

use App\Models\House;
use App\Models\PhotoVersion;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\DB;

/**
 * Which official photo a member shows (share-cards AC 8, AC 11 to AC 13). A member's latest version
 * is the one checked most recently; it is their current photo unless another member's latest version
 * has the same bytes (a placeholder is nobody's photo) or the member is suppressed.
 */
final class Photos
{
    public const CREDITS = ['camara' => 'Foto: Câmara dos Deputados', 'senado' => 'Foto: Agência Senado'];

    /** Every member's latest version, as a SQL source: `latest(id, house, member_source_id, sha256)`. */
    private const LATEST = <<<'SQL'
        with latest as (
            select distinct on (house, member_source_id) id, house, member_source_id, sha256
            from photo_versions order by house, member_source_id, checked_at desc, id desc
        )
        SQL;

    /**
     * The current photo of each listed member that has one, keyed by member source id.
     *
     * @param  list<string>  $ids
     * @return array<string, PhotoVersion>
     */
    public static function current(House $house, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select(self::LATEST."
            select l.id from latest l
            where l.house = ? and l.member_source_id in ({$marks})
              and (select count(*) from latest o where o.sha256 = l.sha256) = 1", [$house->value, ...$ids]);
        $current = [];
        foreach (PhotoVersion::query()->whereIn('id', array_column($rows, 'id'))->get() as $version) {
            if (! self::suppressed($house, $version->member_source_id)) {
                $current[$version->member_source_id] = $version;
            }
        }

        return $current;
    }

    public static function currentOf(House $house, string $id): ?PhotoVersion
    {
        return self::current($house, [$id])[$id] ?? null;
    }

    /**
     * What a page passes to `OfficialPhoto`: the URL on our origin and the house's credit, or null.
     *
     * @return array{url: string, credit: string}|null
     */
    public static function forPage(House $house, string $id): ?array
    {
        $version = self::currentOf($house, $id);

        return $version === null ? null : ['url' => PublicUrl::photo($version->sha256), 'credit' => self::CREDITS[$house->value]];
    }

    public static function suppressed(House $house, string $id): bool
    {
        return in_array("{$house->value}:{$id}", (array) config('mandato.photo_suppressed'), true);
    }

    /** Whether any version of `$sha256` belongs to a suppressed member: its file answers 410. */
    public static function fileSuppressed(string $sha256): bool
    {
        return PhotoVersion::query()->where('sha256', $sha256)->get(['house', 'member_source_id'])
            ->contains(fn (PhotoVersion $v) => self::suppressed(House::from($v->house), $v->member_source_id));
    }
}
