<?php

namespace App\Contract;

/** Reads one house directory of one `schema_version` of the ETL contract into the records the importer stores. */
interface ContractReader
{
    /** @throws ContractException when a file is missing, fails its JSON Schema or names a record that does not resolve */
    public function read(string $houseDir): Snapshot;
}
