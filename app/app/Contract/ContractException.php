<?php

namespace App\Contract;

use RuntimeException;

/** A contract the importer refuses: unsupported, incomplete or failing its JSON Schema. */
class ContractException extends RuntimeException {}
