<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes card images nobody is likely to fetch again (share-cards AC 47, door 9): a PNG older than
 * `--days` whose code is not its subject's latest snapshot. The snapshot stays, so the card renders
 * again from it when asked (AC 48); snapshots and photos are never touched (AC 49).
 */
class PruneCards extends Command
{
    protected $signature = 'mandato:cards:prune
        {--days=30 : Keep every image written fewer than this many days ago}';

    protected $description = 'Delete old card images whose code is not the latest of its subject';

    public function handle(): int
    {
        $days = (string) $this->option('days');
        if (preg_match('/^\d+$/', $days) !== 1) {
            $this->output->getErrorStyle()->writeln('usage: mandato:cards:prune [--days=<days>]');

            return self::FAILURE;
        }
        $cutoff = now()->subDays((int) $days)->getTimestamp();
        $latest = array_flip(array_column(DB::select(
            'select distinct on (kind, house, source_id, legislature) code from card_snapshots order by kind, house, source_id, legislature, id desc'
        ), 'code'));

        $disk = Storage::disk((string) config('mandato.media_disk'));
        $files = 0;
        $bytes = 0;
        $failed = false;
        foreach ($disk->allFiles('cards') as $path) {
            $code = explode('/', $path)[1] ?? '';
            if (isset($latest[$code]) || $disk->lastModified($path) >= $cutoff) {
                continue;
            }
            $size = $disk->size($path);
            if (! $disk->delete($path) || $disk->exists($path)) {
                $this->output->getErrorStyle()->writeln("card prune failed {$path}");
                $failed = true;

                continue;
            }
            $files++;
            $bytes += $size;
        }

        $this->line("cards pruned: {$files} files, {$bytes} bytes");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
