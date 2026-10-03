<?php

namespace App\Http\Controllers;

use App\Cards\Code;
use App\Cards\Payloads;
use App\Cards\Renderer;
use App\Models\CardSnapshot;
use App\Models\House;
use App\Models\Member;
use App\Models\Membership;
use App\Models\RollCall;
use App\Support\PublicUrl;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Share card images (share-cards S3): the current code renders the subject's data and stores its
 * snapshot first (door 8); a stored older code renders its snapshot; any other code redirects to the
 * current card. A rendered PNG is kept and served as is.
 */
class CardController extends Controller
{
    public function __construct(private readonly Renderer $renderer) {}

    public function deputy(string $id, string $n, string $code, string $format): Response
    {
        return $this->member(House::Camara, $id, (int) $n, $code, $format);
    }

    public function senator(string $id, string $n, string $code, string $format): Response
    {
        return $this->member(House::Senado, $id, (int) $n, $code, $format);
    }

    public function camara(string $id, string $code, string $format): Response
    {
        return $this->rollCall(House::Camara, $id, $code, $format);
    }

    public function senado(string $id, string $code, string $format): Response
    {
        return $this->rollCall(House::Senado, $id, $code, $format);
    }

    private function member(House $house, string $id, int $legislature, string $code, string $format): Response
    {
        $member = Member::query()->where('house', $house)->where('source_id', $id)->first();
        $membership = $member?->memberships()->where('legislature_number', $legislature)->first();
        abort_if(! $membership instanceof Membership, 404);
        /** @var Member $member */

        return $this->serve(Payloads::member($house, $member, $membership), PublicUrl::member($house, $id, $legislature), $code, $format);
    }

    private function rollCall(House $house, string $id, string $code, string $format): Response
    {
        $rollCall = RollCall::query()->with('proposition')->where('house', $house)->where('source_id', $id)->first();
        abort_if($rollCall === null, 404);

        return $this->serve(Payloads::rollCall($house, $rollCall), PublicUrl::rollCall($house, $id), $code, $format);
    }

    /** @param  array<string, mixed>  $current */
    private function serve(array $current, string $subjectPath, string $code, string $format): Response
    {
        $currentCode = Code::of($current);
        if ($code === $currentCode) {
            $payload = $current;
            CardSnapshot::query()->insertOrIgnore([
                'code' => $code, 'digest' => Code::digest($payload), 'kind' => $payload['kind'], 'house' => $payload['house'],
                'source_id' => $payload['sourceId'], 'legislature' => $payload['legislature'], 'template' => $payload['template'],
                'payload' => Code::canonical($payload), 'created_at' => now(),
            ]);
        } else {
            $snapshot = CardSnapshot::query()->where('code', $code)->where('kind', $current['kind'])->where('house', $current['house'])
                ->where('source_id', $current['sourceId'])->where('legislature', $current['legislature'])->first();
            if ($snapshot === null) {
                return redirect(PublicUrl::card($subjectPath, $currentCode, $format), 302);
            }
            $payload = $snapshot->payload;
        }

        return $this->image($payload, $code, $format);
    }

    /** @param  array<string, mixed>  $payload */
    private function image(array $payload, string $code, string $format): Response
    {
        $disk = Storage::disk((string) config('mandato.media_disk'));
        $path = "cards/{$code}/{$format}.png";
        if ($disk->exists($path)) {
            return $this->png($disk, $path);
        }

        $locks = $this->locks();
        try {
            // Two requests for one card render it once: the second waits, then finds the file.
            return $locks->lock("card:{$code}:{$format}", 60)->block(20, function () use ($disk, $path, $payload, $code, $format, $locks) {
                // checked again under the lock: the request that held it may have rendered this card
                if ($disk->fileExists($path)) {
                    return $this->png($disk, $path);
                }
                $slot = $this->slot($locks);
                if ($slot === null) {
                    return $this->unavailable();
                }
                try {
                    return $this->render($disk, $path, $payload, $code, $format);
                } finally {
                    $slot->release();
                }
            });
        } catch (LockTimeoutException) {
            return $this->unavailable();
        }
    }

    /** The store holding the render locks: shared by every app instance that shares it. */
    private function locks(): LockProvider
    {
        $store = Cache::store(config('mandato.lock_store'))->getStore();
        if (! $store instanceof LockProvider) {
            throw new RuntimeException('the cache store of mandato.lock_store has no locks');
        }

        return $store;
    }

    /** One of the `card_render_slots` render slots of this store, or null when all are taken (AC 22). */
    private function slot(LockProvider $locks): ?Lock
    {
        for ($i = 0; $i < (int) config('mandato.card_render_slots'); $i++) {
            $lock = $locks->lock("card-render-slot:{$i}", 60);
            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $payload */
    private function render(Filesystem $disk, string $path, array $payload, string $code, string $format): Response
    {
        $started = hrtime(true);
        try {
            $png = $this->renderer->png(Renderer::input($payload, $format, $code));
        } catch (RuntimeException $e) {
            Log::error("card render failed {$code} {$format}: {$e->getMessage()}");

            return $this->unavailable();
        }
        $disk->put($path, $png);
        $ms = intdiv(hrtime(true) - $started, 1_000_000);
        Log::info("card rendered {$code} {$format} {$ms} ms ".strlen($png).' bytes');

        return $this->png($disk, $path, $png);
    }

    private function png(Filesystem $disk, string $path, ?string $bytes = null): Response
    {
        return response($bytes ?? (string) $disk->get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function unavailable(): Response
    {
        return response('', 503, ['Retry-After' => '60']);
    }
}
