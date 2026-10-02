<?php

use Illuminate\Support\Facades\File;
use Tests\Support\ForbiddenTerms;

// Checks C43 and C44 of .specs/features/app-skeleton/checks.md.

test('no forbidden term in app copy', function () {
    $hits = ForbiddenTerms::scan([app_path(), resource_path(), lang_path()], config('forbidden-terms'));

    expect($hits)->toBe([]);
});

test('forbidden matcher is whole word and case-insensitive', function () {
    $dir = sys_get_temp_dir().'/mandato-terms-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($dir);
    File::put("{$dir}/a.vue", '<p>Faltou à sessão</p>');
    File::put("{$dir}/b.blade.php", '<p>Intenção  de voto</p>');
    File::put("{$dir}/c.js", 'const label = "Aprovada"; // faltoso');

    $hits = ForbiddenTerms::scan([$dir], config('forbidden-terms'));

    expect($hits)->toBe([
        ['file' => "{$dir}/a.vue", 'term' => 'faltou'],
        ['file' => "{$dir}/b.blade.php", 'term' => 'intenção de voto'],
    ]);
});

test('forbidden list matches the mvp list', function () {
    $source = File::get(base_path('../site/src/lib/forbidden-terms.ts'));
    preg_match('/FORBIDDEN_TERMS\s*=\s*\[(.*?)\];/s', $source, $list);
    preg_match_all('/"([^"]+)"/u', $list[1] ?? '', $terms);

    expect($terms[1])->toHaveCount(19);
    foreach ($terms[1] as $term) {
        expect(config('forbidden-terms'))->toContain($term);
    }
});
