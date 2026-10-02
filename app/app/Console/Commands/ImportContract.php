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

/** Loads the ETL contract into the database (plan doors 5 and 6). */
class ImportContract extends Command
{
    protected $signature = 'mandato:import
        {dir? : Contract directory written by `mandato-etl build` (default: config mandato.contract_dir)}
        {--dry-run : Validate every file and print what would be imported, writing nothing}';

    protected $description = 'Import the ETL JSON contract into the database';

    public function handle(JsonFiles $files, Importer $importer): int
    {
        $dir = rtrim((string) ($this->argument('dir') ?? config('mandato.contract_dir')), '/');
        if (! is_dir($dir)) {
            return $this->refuse("contract directory not found: {$dir}", self::INVALID);
        }

        try {
            $meta = $files->decode("{$dir}/meta.json");
            $version = $meta instanceof stdClass ? ($meta->schema_version ?? null) : null;
            $reader = ContractReaders::for($version) ?? throw ContractReaders::unsupported($version);
            $snapshot = $reader->read($dir);

            if ($this->option('dry-run')) {
                $this->line("Would import {$snapshot->summary()}");

                return self::SUCCESS;
            }

            $importer->import($snapshot, (string) hash_file('sha256', "{$dir}/meta.json"));
            $this->line("Imported {$snapshot->summary()}");

            return self::SUCCESS;
        } catch (ContractException|ImportLocked $e) {
            return $this->refuse($e->getMessage(), self::FAILURE);
        } catch (QueryException $e) {
            return $this->refuse("import rolled back: {$e->getMessage()}", self::FAILURE);
        }
    }

    private function refuse(string $message, int $code): int
    {
        $this->output->getErrorStyle()->writeln("<error>{$message}</error>");

        return $code;
    }
}
