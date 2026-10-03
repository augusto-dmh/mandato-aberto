<?php

namespace App\Contract;

/** The version seam: one reader per supported `schema_version` (plan door 1; v2 is no longer read). */
final class ContractReaders
{
    public const SUPPORTED_SCHEMA_VERSIONS = [3];

    public static function for(mixed $version): ?ContractReader
    {
        return match ($version) {
            3 => app(V3Reader::class),
            default => null,
        };
    }

    public static function unsupported(mixed $version): ContractException
    {
        $shown = is_int($version) || is_string($version) ? (string) $version : json_encode($version);

        return new ContractException(sprintf(
            'schema_version %s is not supported; expected one of: %s',
            $shown,
            implode(', ', self::SUPPORTED_SCHEMA_VERSIONS),
        ));
    }
}
