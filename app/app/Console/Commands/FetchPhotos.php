<?php

namespace App\Console\Commands;

use App\Media\Photos;
use App\Models\House;
use App\Models\Member;
use App\Models\PhotoVersion;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Keeps one unaltered copy of each member's official photo on the media disk (share-cards S1, door 9):
 * the bytes the house serves, checked as a JPEG and stored under their SHA-256, never re-encoded.
 */
class FetchPhotos extends Command
{
    /** Key of the session-level advisory lock that serialises photo runs; the importer's is 57210057. */
    public const LOCK_KEY = 57_210_058;

    public const USER_AGENT = 'mandato-aberto-app (+https://github.com/augusto-dmh/mandato-aberto)';

    public const HOSTS = ['www.camara.leg.br', 'www.senado.leg.br', 'legis.senado.leg.br'];

    public const USAGE = 'usage: mandato:photos [--house=camara|senado] [--member=<id>] [--stale-after=<days>]';

    private const CONCURRENCY = 4;

    private const PAUSE_MS = 250;

    private const TIMEOUT = 15;

    private const MAX_REDIRECTS = 3;

    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MIN_SIDE = 100;

    /** Batches sent in this run: every batch but the first waits `PAUSE_MS` first. */
    private int $batches = 0;

    protected $signature = 'mandato:photos
        {--house= : camara or senado (default: both)}
        {--member= : One member source id; requires --house}
        {--stale-after=7 : Re-check a current photo checked more than this many days ago}';

    protected $description = 'Cache the official photo of every member from the houses';

    public function handle(): int
    {
        $this->batches = 0;
        $house = $this->option('house');
        $member = $this->option('member');
        $stale = (string) $this->option('stale-after');
        if (($house !== null && House::tryFrom((string) $house) === null) || ($member !== null && $house === null) || preg_match('/^\d+$/', $stale) !== 1) {
            return $this->refuse(self::USAGE, self::INVALID);
        }

        $db = DB::connection();
        if (! $db->selectOne('select pg_try_advisory_lock(?) as locked', [self::LOCK_KEY])->locked) {
            return $this->refuse('another photo run is running', self::FAILURE);
        }
        try {
            $code = self::SUCCESS;
            $houses = $house === null ? House::cases() : [House::from((string) $house)];
            foreach ($houses as $h) {
                if (! $this->runHouse($h, $member === null ? null : (string) $member, (int) $stale)) {
                    $code = self::FAILURE;
                }
            }

            return $code;
        } finally {
            $db->select('select pg_advisory_unlock(?)', [self::LOCK_KEY]);
        }
    }

    /** Fetches the members of one house that need it; false when every fetch failed or storage did. */
    private function runHouse(House $house, ?string $only, int $staleDays): bool
    {
        $members = Member::query()->where('house', $house)
            ->when($only !== null, fn ($q) => $q->where('source_id', $only))
            ->orderBy('source_id')->pluck('photo_url', 'source_id')->all();
        $current = Photos::current($house, array_map('strval', array_keys($members)));
        $limit = Carbon::now()->subDays($staleDays);
        $due = [];
        foreach ($members as $id => $url) {
            $photo = $current[(string) $id] ?? null;
            if ($photo === null || $photo->checked_at->lt($limit)) {
                $due[(string) $id] = (string) $url;
            }
        }

        $counts = ['checked' => 0, 'new' => 0, 'unchanged' => 0, 'failed' => 0];
        $storageFailed = false;
        $batches = array_chunk($due, self::CONCURRENCY, preserve_keys: true);
        foreach ($batches as $batch) {
            if ($this->batches++ > 0) {
                Sleep::for(self::PAUSE_MS)->milliseconds();
            }
            foreach ($this->fetch($batch) as $id => $result) {
                $counts['checked']++;
                $outcome = is_string($result) ? $result : $this->store($house, (string) $id, $batch[$id], $result);
                if (in_array($outcome, ['new', 'unchanged'], true)) {
                    $counts[$outcome]++;

                    continue;
                }
                $counts['failed']++;
                $storageFailed = $storageFailed || $outcome === 'storage write failed';
                $this->output->getErrorStyle()->writeln("photo failed {$house->value} {$id}: {$outcome}");
            }
        }

        $this->line("photos {$house->value}: {$counts['checked']} checked, {$counts['new']} new, {$counts['unchanged']} unchanged, {$counts['failed']} failed");

        return ! $storageFailed && ($counts['checked'] === 0 || $counts['failed'] < $counts['checked']);
    }

    /**
     * Requests a batch at once, following each redirect only to an allowed host over https.
     *
     * @param  array<string, string>  $urls  member id => photo URL
     * @return array<string, array{body: string, final: string}|string> the body and final URL, or why it failed
     */
    private function fetch(array $urls): array
    {
        $results = [];
        $pending = [];
        foreach ($urls as $id => $url) {
            $refusal = $this->refuseUrl($url, initial: true);
            if ($refusal === null) {
                $pending[$id] = $url;
            } else {
                $results[$id] = $refusal;
            }
        }

        for ($redirects = 0; $pending !== []; $redirects++) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $id) => $pool->as($id)->withUserAgent(self::USER_AGENT)->timeout(self::TIMEOUT)->withoutRedirecting()->get($pending[$id]),
                array_map('strval', array_keys($pending)),
            ));
            $next = [];
            foreach ($pending as $id => $url) {
                $response = $responses[$id] ?? null;
                if (! $response instanceof Response) {
                    $results[$id] = 'no response';
                } elseif ($response->redirect() && $response->header('Location') !== '') {
                    $target = $this->resolve($url, $response->header('Location'));
                    $refusal = $redirects >= self::MAX_REDIRECTS ? 'too many redirects' : $this->refuseUrl($target);
                    if ($refusal === null) {
                        $next[$id] = $target;
                    } else {
                        $results[$id] = $refusal;
                    }
                } elseif ($response->status() !== 200) {
                    $results[$id] = "status {$response->status()}";
                } else {
                    $results[$id] = ['body' => $response->body(), 'final' => $url];
                }
            }
            $pending = $next;
        }

        return array_replace(array_intersect_key($urls, $results), $results);
    }

    private function refuseUrl(string $url, bool $initial = false): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! in_array($host, self::HOSTS, true)) {
            return $initial ? 'host not allowed' : "redirect to {$host}";
        }
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return $initial ? 'host not allowed' : 'redirect over http';
        }

        return null;
    }

    private function resolve(string $base, string $location): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $location) === 1) {
            return $location;
        }
        $origin = parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST);

        return str_starts_with($location, '/') ? $origin.$location : $origin.'/'.$location;
    }

    /**
     * Checks the bytes as the house's JPEG and records them (AC 2, AC 3).
     *
     * @param  array{body: string, final: string}  $fetched
     * @return string `new`, `unchanged`, or why it was refused
     */
    private function store(House $house, string $id, string $sourceUrl, array $fetched): string
    {
        $body = $fetched['body'];
        if (strlen($body) > self::MAX_BYTES) {
            return 'larger than 2 MiB';
        }
        $size = str_starts_with($body, "\xFF\xD8\xFF") ? @getimagesizefromstring($body) : false;
        if ($size === false || $size[2] !== IMAGETYPE_JPEG) {
            return 'not a jpeg';
        }
        if ($size[0] < self::MIN_SIDE || $size[1] < self::MIN_SIDE) {
            return 'smaller than 100 x 100';
        }

        $sha = hash('sha256', $body);
        $disk = $this->disk();
        $path = "photos/{$sha}.jpg";
        try {
            $stored = $disk->exists($path) || $disk->put($path, $body);
        } catch (Throwable) {
            $stored = false;
        }
        if (! $stored) {
            return 'storage write failed';
        }

        $now = Carbon::now();
        $latest = PhotoVersion::query()->where('house', $house->value)->where('member_source_id', $id)
            ->orderByDesc('checked_at')->orderByDesc('id')->first();
        if ($latest !== null && $latest->sha256 === $sha) {
            $latest->update(['checked_at' => $now]);

            return 'unchanged';
        }
        $known = PhotoVersion::query()->where(['house' => $house->value, 'member_source_id' => $id, 'sha256' => $sha])->first();
        if ($known !== null) {
            // The house went back to bytes it served before: that version is the latest again.
            $known->update(['checked_at' => $now]);

            return 'new';
        }
        PhotoVersion::query()->create([
            'house' => $house->value, 'member_source_id' => $id, 'sha256' => $sha,
            'source_url' => $sourceUrl, 'final_url' => $fetched['final'], 'bytes' => strlen($body),
            'width' => $size[0], 'height' => $size[1], 'fetched_at' => $now, 'checked_at' => $now,
        ]);

        return 'new';
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('mandato.media_disk'));
    }

    private function refuse(string $message, int $code): int
    {
        $this->output->getErrorStyle()->writeln($message);

        return $code;
    }
}
