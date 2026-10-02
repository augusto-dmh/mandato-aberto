<?php

namespace App\Contract;

/** The version seam (plan door 5): one reader per supported `schema_version`. */
final class ContractReaders
{
    public const SUPPORTED_SCHEMA_VERSIONS = [2];

    public static function for(mixed $version): ?ContractReader
    {
        return match ($version) {
            2 => app(V2Reader::class),
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
