<?php

namespace App\Contract;

/** Reads one `schema_version` of the ETL contract into the normalised records the importer stores. */
interface ContractReader
{
    /** @throws ContractException when a file is missing or fails its JSON Schema */
    public function read(string $dir): Snapshot;
}
