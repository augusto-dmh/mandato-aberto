<?php

use App\Models\PhotoVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

// share-cards AC 9, AC 10, AC 13: the photo route (C15-C17).

beforeEach(function () {
    Storage::fake('media');
    Sleep::fake();
});

/** @return list<string> the directives of a Cache-Control header, sorted */
function cacheDirectives(TestResponse $response): array
{
    $directives = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
    sort($directives);

    return $directives;
}

test('a stored photo is served with immutable headers', function () {
    storeFixturePhotos();
    $sha = PhotoVersion::query()->where('member_source_id', '9101')->value('sha256');

    $response = $this->get("/fotos/{$sha}.jpg");

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($response->getContent())->toBe(Storage::disk('media')->get("photos/{$sha}.jpg"))
        ->and($response->headers->get('ETag'))->toBe("\"{$sha}\"")
        ->and(cacheDirectives($response))->toBe(['immutable', 'max-age=31536000', 'public'])
        ->and($response->headers->has('Set-Cookie'))->toBeFalse();
});

test('photo paths that are not a stored sha256 answer 404', function () {
    storeFixturePhotos();
    $sha = PhotoVersion::query()->where('member_source_id', '101')->value('sha256');

    foreach ([
        '/fotos/'.strtoupper($sha).'.jpg',
        '/fotos/'.substr($sha, 1).'.jpg',
        '/fotos/'.substr($sha, 1).'g.jpg',
        '/fotos/'.hash('sha256', 'not stored').'.jpg',
        "/fotos/{$sha}",
    ] as $path) {
        $this->get($path)->assertStatus(404);
    }
});

test('a suppressed member\'s photos answer 410', function () {
    storeFixturePhotos();
    $sha101 = PhotoVersion::query()->where('member_source_id', '101')->value('sha256');
    $sha102 = PhotoVersion::query()->where('member_source_id', '102')->value('sha256');
    config(['mandato.photo_suppressed' => ['camara:101']]);

    $this->get("/fotos/{$sha101}.jpg")->assertStatus(410);
    $this->get("/fotos/{$sha102}.jpg")->assertStatus(200);
});
