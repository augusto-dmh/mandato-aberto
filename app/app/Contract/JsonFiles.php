<?php

namespace App\Contract;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

/** Reads contract files and validates each one against its schema in `mandato.schema_dir` (door 7). */
final class JsonFiles
{
    private readonly Validator $validator;

    /** @var array<string, Schema> */
    private array $schemas = [];

    public function __construct(private readonly string $schemaDir)
    {
        $this->validator = new Validator;
    }

    public function decode(string $path): mixed
    {
        if (! is_file($path)) {
            throw new ContractException("{$path}: file not found");
        }
        $data = json_decode((string) file_get_contents($path));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ContractException("{$path}: not valid JSON (".json_last_error_msg().')');
        }

        return $data;
    }

    /** Decodes `$path` and fails on the first JSON pointer that breaks `$schema`. */
    public function validated(string $path, string $schema): mixed
    {
        $data = $this->decode($path);
        $error = $this->validator->validate($data, $this->schema($schema))->error();
        if ($error !== null) {
            $first = $this->deepest($error);
            $pointer = '/'.implode('/', array_map(strval(...), $first->data()->fullPath()));
            throw new ContractException("{$path}: {$pointer}: {$first->message()} ({$schema})");
        }

        return $data;
    }

    private function schema(string $name): Schema
    {
        if (! isset($this->schemas[$name])) {
            $path = "{$this->schemaDir}/{$name}";
            if (! is_file($path)) {
                throw new ContractException("schema not found: {$path}");
            }
            $schema = json_decode((string) file_get_contents($path));
            // The ETL's schemas carry a relative `$id`; opis needs an absolute one, so anchor it at the file.
            $schema->{'$id'} = 'file://'.realpath($path);
            $this->schemas[$name] = $this->validator->loader()->loadObjectSchema($schema);
        }

        return $this->schemas[$name];
    }

    private function deepest(ValidationError $error): ValidationError
    {
        while ($error->subErrors() !== []) {
            $error = $error->subErrors()[0];
        }

        return $error;
    }
}
