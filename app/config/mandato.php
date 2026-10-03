<?php

return [

    /*
    | The ETL contract `mandato:import` reads when no directory is given, and the JSON Schema
    | files every contract file is validated against (plan doors 5 and 7). Both sit beside
    | `app/` in the repository; Sail mounts them at the same relative paths.
    */

    'contract_dir' => env('MANDATO_CONTRACT_DIR', base_path('../data/out')),

    'schema_dir' => env('MANDATO_SCHEMA_DIR', base_path('../etl/schema')),

    /*
    | Every number links to its method. The app has no Metodologia page yet, so the notes point
    | to the live MVP's (plan, Assumptions).
    */

    'method_url' => 'https://augusto-dmh.github.io/mandato-aberto/metodologia/',

];
