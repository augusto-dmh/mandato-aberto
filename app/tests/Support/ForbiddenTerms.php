<?php

namespace Tests\Support;

use Symfony\Component\Finder\Finder;

/** Finds the forbidden terms (`config/forbidden-terms.php`) in our own copy, as the MVP's `findForbidden` does. */
final class ForbiddenTerms
{
    /** Whole word, case-insensitive, any run of whitespace between words. */
    public static function pattern(string $term): string
    {
        $words = array_map(fn (string $w) => preg_quote($w, '/'), explode(' ', $term));

        return '/(?<![\p{L}\p{N}])'.implode('\s+', $words).'(?![\p{L}\p{N}])/iu';
    }

    /**
     * @param  list<string>  $roots  directories; one that does not exist holds nothing
     * @param  list<string>  $terms
     * @return list<array{file: string, term: string}>
     */
    public static function scan(array $roots, array $terms): array
    {
        $existing = array_values(array_filter($roots, is_dir(...)));
        if ($existing === []) {
            return [];
        }
        $hits = [];
        foreach ((new Finder)->files()->in($existing)->sortByName() as $file) {
            $text = $file->getContents();
            foreach ($terms as $term) {
                if (preg_match(self::pattern($term), $text) === 1) {
                    $hits[] = ['file' => $file->getPathname(), 'term' => $term];
                }
            }
        }

        return $hits;
    }
}
