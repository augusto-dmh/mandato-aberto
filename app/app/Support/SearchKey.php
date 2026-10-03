<?php

namespace App\Support;

use Normalizer;

/** The one normaliser of names and queries for search (app-home door 3). */
final class SearchKey
{
    /** NFD, combining marks removed, lower case, whitespace runs collapsed to one space, trimmed. */
    public static function of(string $s): string
    {
        $plain = (string) preg_replace('/\p{Mn}+/u', '', (string) Normalizer::normalize($s, Normalizer::FORM_D));

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($plain)));
    }
}
