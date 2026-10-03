<?php

use App\Models\PhotoVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

// share-cards S2: member pages show the photo with the house's credit (C18-C21; C20's card half in CardImageTest).

beforeEach(function () {
    Storage::fake('media');
    Sleep::fake();
});

test('member pages show the official photo with the house credit', function () {
    requireSsr();
    storeFixturePhotos();

    foreach ([['/deputados/101/', '101', 'Ana Souza', 'Foto: Câmara dos Deputados'], ['/senadores/9101/', '9101', 'Rosa Andrade', 'Foto: Agência Senado']] as [$path, $id, $name, $credit]) {
        $sha = PhotoVersion::query()->where('member_source_id', $id)->value('sha256');
        $response = $this->get($path);
        $doc = html($response);
        $imgs = $doc->querySelectorAll('.ma-hero img');
        expect($imgs->length)->toBe(1)
            ->and($imgs->item(0)->getAttribute('src'))->toBe("/fotos/{$sha}.jpg")
            ->and($imgs->item(0)->getAttribute('alt'))->toBe("Foto oficial de {$name}")
            ->and(textOf($doc->querySelector('.ma-hero figcaption')))->toBe($credit)
            ->and($response->viewData('page')['props']['photo'])->toBe(['url' => "/fotos/{$sha}.jpg", 'credit' => $credit]);
    }
});

test('a member without a current photo shows the initials', function () {
    requireSsr();
    importFixtures();

    $response = $this->get('/deputados/103/');
    $doc = html($response);

    expect(textOf($doc->querySelector('.ma-hero .ma-photo__initials')))->toBe('CD')
        ->and($doc->querySelector('.ma-hero figcaption'))->toBeNull()
        ->and($doc->querySelector('.ma-hero img'))->toBeNull()
        ->and($response->viewData('page')['props']['photo'])->toBeNull();
});

test('member pages load no image from another origin', function () {
    requireSsr();
    storeFixturePhotos();

    foreach (['/deputados/101/', '/deputados/101/legislatura/57/', '/deputados/102/', '/deputados/103/', '/senadores/9101/', '/senadores/9103/'] as $path) {
        $srcs = array_map(fn ($img) => (string) $img->getAttribute('src'), iterator_to_array(html($this->get($path))->querySelectorAll('img')));
        expect($srcs)->not->toBe([], $path);
        foreach ($srcs as $src) {
            expect(preg_match('#^/(?!/)#', $src))->toBe(1, "{$path}: {$src}");
        }
    }
});
