<?php

namespace App\Cards;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * The verification code of a card (share-cards door 5): the Brasília date of the data, then the first
 * 40 bits of the SHA-256 of the canonical payload in Crockford base32. The same data always gives the
 * same code, and any shown value, the photo, the data's date or the template changes it.
 */
final class Code
{
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public const PATTERN = '[0-9]{8}-[0-9A-HJKMNP-TV-Z]{8}';

    /**
     * Keys sorted recursively, lists kept in order, Unicode and slashes unescaped, no whitespace.
     *
     * @param  array<array-key, mixed>  $payload  integers, strings, null and arrays of them only
     */
    public static function canonical(array $payload): string
    {
        return (string) json_encode(self::sorted($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param  array<array-key, mixed>  $payload */
    public static function digest(array $payload): string
    {
        return hash('sha256', self::canonical($payload));
    }

    /** @param  array<array-key, mixed>  $payload  with `generatedAt`, an ISO 8601 instant */
    public static function of(array $payload): string
    {
        $date = CarbonImmutable::parse((string) $payload['generatedAt'])->setTimezone('America/Sao_Paulo')->format('Ymd');
        $bits = (int) hexdec(substr(self::digest($payload), 0, 10));
        $code = '';
        for ($shift = 35; $shift >= 0; $shift -= 5) {
            $code .= self::ALPHABET[($bits >> $shift) & 31];
        }

        return "{$date}-{$code}";
    }

    /** What a reader typed, in canonical form: case, spaces, hyphens and look-alikes forgiven; null when it cannot be a code. */
    public static function normalise(string $typed): ?string
    {
        $plain = strtr(strtoupper((string) preg_replace('/[\s\-]+/u', '', $typed)), ['O' => '0', 'I' => '1', 'L' => '1']);
        if (strlen($plain) !== 16) {
            return null;
        }
        $code = substr($plain, 0, 8).'-'.substr($plain, 8);

        return preg_match('/^'.self::PATTERN.'$/', $code) === 1 ? $code : null;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function sorted(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sorted($item);
            } elseif (! is_int($item) && ! is_string($item) && $item !== null) {
                throw new InvalidArgumentException("card payload value at {$key} is neither an integer nor a string");
            }
        }

        return $value;
    }
}
