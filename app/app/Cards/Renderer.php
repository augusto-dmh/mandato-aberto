<?php

namespace App\Cards;

use App\Media\Photos;
use App\Models\House;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Turns a card payload into what the render CLI reads, and runs it (share-cards door 3): one Node
 * process per card, bounded by `mandato.card_render_timeout`.
 */
final class Renderer
{
    /** URL format => the design package's format prop. */
    public const FORMATS = ['1200x630' => 'og', '1080x1350' => 'feed', '1080x1920' => 'story'];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{kind: string, format: string, props: array<string, mixed>, photo: string|null}
     */
    public static function input(array $payload, string $format, string $code): array
    {
        $house = House::from((string) $payload['house']);
        $common = ['house' => $house->value, 'generatedAt' => $payload['generatedAt'], 'code' => $code, 'verifyHost' => self::verifyHost()];
        if ($payload['kind'] === 'roll_call') {
            $props = [...$common,
                'heading' => $payload['heading'], 'date' => $payload['date'], 'ballot' => $payload['ballot'], 'kind' => $payload['rollCallKind'],
                'approved' => $payload['approved'] === null ? null : (bool) $payload['approved'], 'tallies' => $payload['tallies'],
            ];

            return ['kind' => 'roll_call', 'format' => self::FORMATS[$format], 'props' => $props, 'photo' => null];
        }

        $props = [...$common,
            'legislature' => $payload['legislature'],
            'member' => ['name' => $payload['name'], 'party' => $payload['party'], 'uf' => $payload['uf']],
            'figures' => $payload['figures'], 'votes' => $payload['votes'], 'photoCredit' => Photos::CREDITS[$house->value],
        ];

        return ['kind' => 'member', 'format' => self::FORMATS[$format], 'props' => $props, 'photo' => self::photo($house, (string) $payload['sourceId'], $payload['photoSha256'])];
    }

    /**
     * The PNG of one card; throws with the reason when the CLI fails or runs too long.
     *
     * @param  array<string, mixed>  $input
     */
    public function png(array $input): string
    {
        return $this->run($input, []);
    }

    /**
     * The card's HTML page, without starting the browser.
     *
     * @param  array<string, mixed>  $input
     */
    public function html(array $input): string
    {
        return $this->run($input, ['--html']);
    }

    /** The host of `APP_URL`, printed on every card (plan open question 1). */
    public static function verifyHost(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /**
     * The photo the payload names, as a data URI, unless the member is suppressed now or the file is gone (AC 13).
     */
    private static function photo(House $house, string $id, mixed $sha256): ?string
    {
        if (! is_string($sha256) || Photos::suppressed($house, $id)) {
            return null;
        }
        $disk = Storage::disk((string) config('mandato.media_disk'));
        $path = "photos/{$sha256}.jpg";

        return $disk->exists($path) ? 'data:image/jpeg;base64,'.base64_encode((string) $disk->get($path)) : null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $flags
     */
    private function run(array $input, array $flags): string
    {
        try {
            $result = Process::timeout((int) config('mandato.card_render_timeout'))
                ->env(array_filter(['PLAYWRIGHT_BROWSERS_PATH' => (string) config('mandato.card_browsers_path')]))
                ->input(json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))
                ->run([...(array) config('mandato.card_renderer'), ...$flags]);
        } catch (ProcessTimedOutException) {
            throw new RuntimeException('timed out');
        }
        if (! $result->successful()) {
            $reason = trim(collect(explode("\n", trim($result->errorOutput())))->reject(fn ($l) => str_starts_with($l, 'blocked '))->last() ?? '');
            throw new RuntimeException($reason === '' ? "exit {$result->exitCode()}" : $reason);
        }

        return $result->output();
    }
}
