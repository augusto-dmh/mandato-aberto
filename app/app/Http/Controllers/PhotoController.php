<?php

namespace App\Http\Controllers;

use App\Media\Photos;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Serves an official photo from our origin, as fetched (share-cards AC 9, AC 10, AC 13). */
class PhotoController extends Controller
{
    public function show(string $sha256): Response
    {
        $disk = Storage::disk((string) config('mandato.media_disk'));
        $path = "photos/{$sha256}.jpg";
        abort_unless($disk->exists($path), 404);
        abort_if(Photos::fileSuppressed($sha256), 410);

        return response((string) $disk->get($path), 200, [
            'Content-Type' => 'image/jpeg',
            'ETag' => "\"{$sha256}\"",
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
