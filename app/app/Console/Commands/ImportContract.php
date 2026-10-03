<?php

namespace App\Console\Commands;

use App\Contract\ContractException;
use App\Contract\ContractReaders;
use App\Contract\JsonFiles;
use App\Import\Importer;
use App\Import\ImportLocked;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use stdClass;

/**
 * Loads the ETL contract into the database (plan door 2): a directory with `meta.json` is one
 * house; otherwise its `camara/` and then its `senado/` are, each validated and committed in its
 * own transaction, all under one advisory lock.
 */
class ImportContract extends Command
{
    /** The house directories a parent directory may hold, in import order. */
    public const HOUSES = ['camara', 'senado'];

    protected $signature = 'mandato:import
        {dir? : A house directory, or a directory holding camara/ and senado/ (default: config mandato.contract_dir)}
        {--dry-run : Validate every file and print what would be imported, writing nothing}';

    protected $description = 'Import the ETL JSON contract into the database';

    public function handle(JsonFiles $files, Importer $importer): int
    {
        $dir = rtrim((string) ($this->argument('dir') ?? config('mandato.contract_dir')), '/');
        if (! is_dir($dir)) {
            return $this->refuse("contract directory not found: {$dir}", self::INVALID);
        }

        if (is_file("{$dir}/meta.json")) {
            $houses = [$dir];
        } else {
            $houses = array_values(array_filter(array_map(fn (string $h) => "{$dir}/{$h}", self::HOUSES), fn (string $d) => is_file("{$d}/meta.json")));
            if ($houses === []) {
                return $this->refuse("no contract found in {$dir}", self::FAILURE);
            }
        }
        $parent = count($houses) > 1 || $houses[0] !== $dir;

        try {
            return $importer->locked(function () use ($houses, $parent, $files, $importer) {
                $code = self::SUCCESS;
                foreach ($houses as $houseDir) {
                    $error = $this->importHouse($houseDir, $files, $importer);
                    if ($error !== null) {
                        $this->refuse($parent ? "{$houseDir}: {$error}" : $error, self::FAILURE);
                        $code = self::FAILURE;
                    }
                }

                return $code;
            });
        } catch (ImportLocked $e) {
            return $this->refuse($e->getMessage(), self::FAILURE);
        }
    }

    /** Imports one house directory; returns why it was refused or rolled back, or null. */
    private function importHouse(string $houseDir, JsonFiles $files, Importer $importer): ?string
    {
        try {
            $meta = $files->decode("{$houseDir}/meta.json");
            $version = $meta instanceof stdClass ? ($meta->schema_version ?? null) : null;
            $reader = ContractReaders::for($version) ?? throw ContractReaders::unsupported($version);
            $snapshot = $reader->read($houseDir);

            if ($this->option('dry-run')) {
                $importer->checkLegislatures($snapshot);
                $this->line("Would import {$snapshot->summary()}");

                return null;
            }

            $importer->import($snapshot, (string) hash_file('sha256', "{$houseDir}/meta.json"));
            $this->line("Imported {$snapshot->summary()}");

            return null;
        } catch (ContractException $e) {
            return $e->getMessage();
        } catch (QueryException $e) {
            return "import rolled back: {$e->getMessage()}";
        }
    }

    private function refuse(string $message, int $code): int
    {
        $this->output->getErrorStyle()->writeln("<error>{$message}</error>");

        return $code;
    }
}
