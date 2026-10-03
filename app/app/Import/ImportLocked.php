<?php

namespace App\Import;

use RuntimeException;

/** Another `mandato:import` holds the advisory lock. */
class ImportLocked extends RuntimeException {}
