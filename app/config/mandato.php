<?php

return [

    /*
    | The ETL contract `mandato:import` reads when no directory is given (one directory per house,
    | contract-v3 door 1), and the JSON Schema files every contract file is validated against, under
    | `v3/` (plan doors 1 and 2). Both sit beside `app/` in the repository; Sail mounts them at the
    | same relative paths.
    */

    'contract_dir' => env('MANDATO_CONTRACT_DIR', base_path('../data/v3')),

    'schema_dir' => env('MANDATO_SCHEMA_DIR', base_path('../etl/schema')),

];
