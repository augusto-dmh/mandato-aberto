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

    /*
    | Official photos and share cards (share-cards plan). The disk holds `photos/{sha256}.jpg` and
    | `cards/{code}/{format}.png` (door 1); the deploy feature chooses its driver. A member listed
    | here as `<house>:<id>` is shown with initials and their photo files answer 410 (AC 13).
    */

    'media_disk' => env('MANDATO_MEDIA_DISK', 'media'),

    'photo_suppressed' => [],

    // Raised whenever a card's content rules change: every code changes with it (door 5).
    'card_template' => 1,

    // The card renderer (door 3): one Node process per render, at most `card_render_slots` at once.
    'card_renderer' => ['node', base_path('bootstrap/cards/render.mjs')],

    'card_render_timeout' => 15,

    'card_render_slots' => 2,

    // The cache store holding the render locks; null is the default store.
    'lock_store' => env('MANDATO_LOCK_STORE'),

];
