<?php

use App\Support\PublicUrl;

// share-cards door 6: every new public path has the one builder (C72).

test('public url builds photo card and verification paths', function () {
    $sha = str_repeat('0a', 32);
    $code = '20270301-K7Q29XPD';

    expect(PublicUrl::photo($sha))->toBe("/fotos/{$sha}.jpg")
        ->and(PublicUrl::card(PublicUrl::member('camara', '101', 58), $code, '1200x630'))->toBe("/deputados/101/legislatura/58/card/{$code}/1200x630.png")
        ->and(PublicUrl::card(PublicUrl::member('senado', '9101', 57), $code, '1080x1350'))->toBe("/senadores/9101/legislatura/57/card/{$code}/1080x1350.png")
        ->and(PublicUrl::card(PublicUrl::rollCall('camara', '100-1'), $code, '1080x1920'))->toBe("/votacoes/100-1/card/{$code}/1080x1920.png")
        ->and(PublicUrl::card(PublicUrl::rollCall('senado', '7001'), $code, '1200x630'))->toBe("/senado/votacoes/7001/card/{$code}/1200x630.png")
        ->and(PublicUrl::verify())->toBe('/verificar/')
        ->and(PublicUrl::verify($code))->toBe("/verificar/{$code}/");
});
