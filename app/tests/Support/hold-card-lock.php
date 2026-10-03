<?php

// Test helper (share-cards C31): another request's render in flight. It holds one card's lock in the
// file cache store, signals that it does, writes the PNG it read on stdin a second later, then releases.
//   php tests/Support/hold-card-lock.php <lock name> <png path> <ready file> < png

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $name, $file, $ready] = $argv;
$png = (string) stream_get_contents(STDIN);
$lock = Cache::store('file')->lock($name, 30);
if (! $lock->get()) {
    fwrite(STDERR, "lock {$name} is held\n");
    exit(1);
}
touch($ready);
sleep(1);
if (! is_dir(dirname($file))) {
    mkdir(dirname($file), 0777, true);
}
file_put_contents($file, $png);
$lock->release();
