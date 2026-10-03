<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

// Checks C39 and C45-C48 of .specs/features/app-skeleton/checks.md (C47 now C72 of app-contract-v3): the files the gates and doors live in.

test('ci job app runs every gate', function () {
    $job = Yaml::parseFile(base_path('../.github/workflows/ci.yml'))['jobs']['app'] ?? null;
    expect($job)->not->toBeNull()
        ->and($job['services']['postgres']['image'])->toBe('postgres:18');

    $steps = $job['steps'];
    $php = collect($steps)->first(fn ($s) => str_starts_with($s['uses'] ?? '', 'shivammathur/setup-php'));
    $node = collect($steps)->first(fn ($s) => str_starts_with($s['uses'] ?? '', 'actions/setup-node'));
    expect((string) $php['with']['php-version'])->toBe('8.5')
        ->and($php['with']['extensions'])->toContain('pdo_pgsql')->toContain('intl')
        ->and((string) $node['with']['node-version'])->toBe('24');

    $runs = collect($steps)->filter(fn ($s) => isset($s['run']))
        ->map(fn ($s) => ['dir' => $s['working-directory'] ?? $job['defaults']['run']['working-directory'] ?? '.', 'run' => $s['run']])
        ->values();
    $ran = fn (string $dir, string $command) => $runs->contains(fn ($s) => $s['dir'] === $dir && str_contains($s['run'], $command));
    foreach ([['design', 'npm ci'], ['design', 'npm run build'], ['app', 'composer install'], ['app', 'pint --test'],
        ['app', 'phpstan analyse'], ['app', 'npm ci'], ['app', 'npm run build'], ['app', 'inertia:start-ssr'], ['app', 'php artisan test']] as [$dir, $command]) {
        expect($ran($dir, $command))->toBeTrue("job app does not run `{$command}` in {$dir}/");
    }
    foreach ($steps as $step) {
        expect($step)->not->toHaveKey('continue-on-error');
    }
    expect($job)->not->toHaveKey('continue-on-error')
        ->and(Yaml::parseFile(base_path('phpstan.neon'))['parameters']['level'])->toBe(6)
        // the client and SSR bundles, then the card renderer beside them (share-cards door 3)
        ->and(File::get(base_path('package.json')))->toContain('"build": "vite build && vite build --ssr && vite build --config vite.cards.config.js"');
});

test('readme names the commands', function () {
    $readme = File::get(base_path('README.md'));

    foreach (['./vendor/bin/sail up -d', 'sail artisan mandato:import', 'inertia:start-ssr', 'sail artisan test'] as $command) {
        expect($readme)->toContain($command);
    }
});

test('declares the door 1 versions', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true);
    expect($composer['require'])->toMatchArray([
        'php' => '^8.4',
        'laravel/framework' => '^13.34',
        'inertiajs/inertia-laravel' => '^3.5',
        'opis/json-schema' => '^2.6',
    ])->and($composer['require-dev'])->toMatchArray([
        'pestphp/pest' => '^5.3',
        'pestphp/pest-plugin-laravel' => '^5.0',
        'larastan/larastan' => '^3.12',
        'laravel/pint' => '^1.32',
        'laravel/sail' => '^1.68',
    ]);

    $package = json_decode(File::get(base_path('package.json')), true);
    expect([...$package['dependencies'], ...$package['devDependencies']])->toMatchArray([
        'vue' => '^3.5.43',
        '@inertiajs/vue3' => '^3.8',
        '@inertiajs/vite' => '^3.8',
        'vite' => '^8.3',
        'laravel-vite-plugin' => '^3.2',
        '@vitejs/plugin-vue' => '^6.0.9',
    ]);
});

test('consumes the design package', function () {
    $package = json_decode(File::get(base_path('package.json')), true);
    expect($package['dependencies']['mandato-design'])->toBe('file:../design')
        ->and(File::get(base_path('vite.config.js')))->toMatch('/ssr:\s*\{\s*noExternal:\s*\[\s*["\']mandato-design["\']\s*\]/');

    $design = json_decode(File::get(base_path('../design/package.json')), true);
    expect($design['exports'])->toHaveKey('./styles/*')
        ->and($design['exports']['./styles/*'])->toBe('./styles/*');

    foreach (['Members/Show.vue', 'RollCalls/Show.vue', 'Methodology/Show.vue'] as $page) {
        expect(File::get(resource_path("js/Pages/{$page}")))->toMatch('/from\s+["\']mandato-design\/components\//');
    }
    expect(File::exists(resource_path('js/Pages/Deputies/Show.vue')))->toBeFalse();
});

test('sail runs php 8.5 with the sibling mounts', function () {
    $services = Yaml::parseFile(base_path('compose.yaml'))['services'];

    // The published Sail runtime adds the card renderer's browser shell (share-cards door 3, C36).
    expect($services['laravel.test']['build']['context'])->toBe('./docker/8.5')
        ->and($services['pgsql']['image'])->toBe('postgres:18-alpine')
        ->and($services['laravel.test']['volumes'])->toContain(
            '../design:/var/www/design',
            '../etl:/var/www/etl:ro',
            '../data:/var/www/data:ro',
            '../site:/var/www/site:ro',
            '../.github:/var/www/.github:ro',
        );
});
