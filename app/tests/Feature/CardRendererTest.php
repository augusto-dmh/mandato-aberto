<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Jpeg;

// share-cards door 3: the render CLI, its sources, its network and its weight (C32-C36).

/**
 * Runs the built render CLI with `$input` as JSON on stdin.
 *
 * @param  array<string, mixed>|string  $input
 * @param  array<string, string>  $env
 */
function renderCli(array|string $input, array $flags = [], array $env = []): Process
{
    $process = new Process(['node', base_path('bootstrap/cards/render.mjs'), ...$flags], base_path(), $env === [] ? null : $env, null, 60);
    $process->setInput(is_string($input) ? $input : json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $process->run();

    return $process;
}

/** A member card's CLI input with the longest name of the design fixtures and `$votes` plenary votes. */
function longestNameInput(string $format, ?string $photo, int $votes = 240): array
{
    $positions = ['yes', 'no', 'yes', 'abstention', 'yes', 'obstruction', 'no', 'notVoting', 'yes', 'presiding'];

    return [
        'kind' => 'member', 'format' => $format, 'photo' => $photo,
        'props' => [
            'house' => 'senado', 'legislature' => 57, 'member' => ['name' => 'Luiz Philippe de Orleans e Bragança', 'party' => 'PL', 'uf' => 'SP'],
            'figures' => [
                ['label' => 'Participação em votações nominais do plenário', 'count' => 1234, 'total' => 2345],
                ['label' => 'Votos iguais à orientação do governo', 'count' => 812, 'total' => 1201],
                ['label' => 'Votos iguais à maioria do próprio partido', 'count' => 1100, 'total' => 1201],
            ],
            'votes' => array_map(fn (int $i) => [
                'rollCallId' => (1000 + $i).'-1', 'date' => sprintf('20%02d-%02d-%02d', 23 + intdiv($i, 80), 1 + intdiv($i, 8) % 10, 1 + ($i % 8) * 3),
                'position' => $positions[$i % 10],
            ], range(0, $votes - 1)),
            'generatedAt' => '2027-03-05T12:00:00Z', 'code' => '20270305-K7Q29XPD', 'verifyHost' => 'mandato.test', 'photoCredit' => 'Foto: Agência Senado',
        ],
    ];
}

test('the card entry builds only from the design package', function () {
    $source = File::get(resource_path('js/cards/render.js'));
    preg_match_all('/^import\s+(?:[^"\']+\s+from\s+)?["\']([^"\']+)["\'];/m', $source, $imports);
    $design = array_values(array_filter($imports[1], fn (string $m) => str_starts_with($m, 'mandato-design')));

    expect($design)->toEqualCanonicalizing([
        'mandato-design/components/MemberCard.vue',
        'mandato-design/components/RollCallCard.vue',
        'mandato-design/styles/components.css?raw',
        'mandato-design/tokens.css?raw',
    ])
        ->and(array_values(array_diff($imports[1], $design)))->toEqualCanonicalizing(['node:fs', 'node:module', 'node:path', 'node:url', 'vue', 'vue/server-renderer'])
        ->and($source)->toContain('["@fontsource-variable/archivo", "wdth.css"]')
        ->and($source)->toContain('["@fontsource-variable/source-serif-4", "opsz.css"]')
        ->and(preg_match_all('/@fontsource-variable\/[a-z0-9-]+/', $source, $fonts))->toBe(2);
});

test('the renderer aborts every request that is not data', function () {
    requireCardRenderer();
    $docroot = sys_get_temp_dir().'/probe-'.bin2hex(random_bytes(4));
    mkdir($docroot);
    file_put_contents("{$docroot}/router.php", '<?php file_put_contents(__DIR__."/hits.log", $_SERVER["REQUEST_URI"]."\n", FILE_APPEND); http_response_code(200);');
    $port = 18000 + random_int(0, 999);
    $server = new Process(['php', '-S', "127.0.0.1:{$port}", "{$docroot}/router.php"], $docroot);
    $server->start();
    for ($i = 0, $up = false; $i < 50 && ! $up; $i++) {
        usleep(100_000);
        $up = @file_get_contents("http://127.0.0.1:{$port}/warmup") !== false;
    }
    expect($up)->toBeTrue($server->getErrorOutput());
    unlink("{$docroot}/hits.log");

    $html = '<!doctype html><html><head><link rel="stylesheet" href="http://127.0.0.1:'.$port.'/style.css"></head>'
        .'<body><div class="ma-card" style="width:100px;height:100px"><img src="http://127.0.0.1:'.$port.'/probe.jpg"></div></body></html>';
    // the page first: with the module path in argv[1] the bundle would run as the CLI
    $script = 'const m = await import(process.argv[2]); const { blocked } = await m.capture(process.argv[1], { width: 200, height: 200 }); for (const u of blocked) console.error(`blocked ${u}`);';
    $node = new Process(['node', '--input-type=module', '-e', $script, $html, base_path('bootstrap/cards/render.mjs')], base_path(), null, null, 60);
    $node->run();
    $server->stop();

    expect($node->getExitCode())->toBe(0, $node->getErrorOutput())
        ->and(is_file("{$docroot}/hits.log"))->toBeFalse()
        ->and($node->getErrorOutput())->toContain("blocked http://127.0.0.1:{$port}/probe.jpg")
        ->and($node->getErrorOutput())->toContain("blocked http://127.0.0.1:{$port}/style.css");
});

test('cards stay under their weight budget', function () {
    requireCardRenderer();
    $photo = 'data:image/jpeg;base64,'.base64_encode(Jpeg::make(480, 600, 9101));

    foreach (['og' => 300_000, 'feed' => 500_000, 'story' => 800_000] as $format => $limit) {
        $process = renderCli(longestNameInput($format, $photo));
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
        $bytes = strlen($process->getOutput());
        expect($bytes)->toBeLessThanOrEqual($limit, "{$format}: {$bytes} bytes");
    }
});

test('the render cli exit codes', function () {
    requireCardRenderer();
    $valid = longestNameInput('og', null, 3);

    foreach ([
        'not json' => '{"kind":',
        'unknown kind' => [...$valid, 'kind' => 'profile'],
        'unknown format' => [...$valid, 'format' => '1200x630'],
        'photo url' => [...$valid, 'photo' => 'https://www.camara.leg.br/internet/deputado/bandep/101.jpg'],
        'png photo' => [...$valid, 'photo' => 'data:image/png;base64,iVBORw0KGgo='],
    ] as $case => $input) {
        $process = renderCli($input);
        expect($process->getExitCode())->toBe(2, $case)
            ->and(trim($process->getErrorOutput()))->not->toBe('', $case)
            ->and($process->getOutput())->toBe('', $case);
    }

    $empty = sys_get_temp_dir().'/no-browsers-'.bin2hex(random_bytes(4));
    mkdir($empty);
    $process = renderCli($valid, [], [...getenv(), 'PLAYWRIGHT_BROWSERS_PATH' => $empty]);
    expect($process->getExitCode())->toBe(1)
        ->and(trim($process->getErrorOutput()))->not->toBe('');

    $process = renderCli($valid);
    expect($process->getExitCode())->toBe(0, $process->getErrorOutput())
        ->and(substr($process->getOutput(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

test('the browser shell is installed where cards render', function () {
    $app = json_decode(File::get(base_path('package.json')), true);
    $appLock = json_decode(File::get(base_path('package-lock.json')), true);
    $designLock = json_decode(File::get(base_path('../design/package-lock.json')), true);
    $dockerfile = File::get(base_path('docker/8.5/Dockerfile'));
    preg_match('/^ARG PLAYWRIGHT_VERSION=(\S+)$/m', $dockerfile, $version);

    expect($app['dependencies']['playwright-core'] ?? $app['devDependencies']['playwright-core'] ?? null)->toBe('^1.63')
        ->and($dockerfile)->toContain('playwright@$PLAYWRIGHT_VERSION install --with-deps --only-shell chromium')
        ->and($version[1] ?? null)->toBe($appLock['packages']['node_modules/playwright-core']['version'])
        ->and($version[1])->toBe($designLock['packages']['node_modules/@playwright/test']['version'])
        ->and(Yaml::parseFile(base_path('compose.yaml'))['services']['laravel.test']['build']['context'])->toBe('./docker/8.5')
        ->and(File::get(base_path('package.json')))->toContain('vite build --config vite.cards.config.js');

    $steps = Yaml::parseFile(base_path('../.github/workflows/ci.yml'))['jobs']['app']['steps'];
    $runs = array_map(fn (array $s) => $s['run'] ?? '', $steps);
    $install = array_key_first(array_filter($runs, fn (string $r) => str_contains($r, 'playwright install --with-deps --only-shell chromium')));
    $build = array_key_first(array_filter($runs, fn (string $r) => str_contains($r, 'npm run build')));
    $test = array_key_first(array_filter($runs, fn (string $r) => str_contains($r, 'php artisan test')));
    expect($install)->not->toBeNull()
        ->and($build)->not->toBeNull()
        ->and($install)->toBeLessThan($test)
        ->and($build)->toBeLessThan($test);
});
