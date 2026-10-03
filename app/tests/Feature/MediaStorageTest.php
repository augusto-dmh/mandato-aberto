<?php

use Illuminate\Support\Facades\DB;

// share-cards doors 1 and 2: where photos and cards live, and the tables that remember them (C70, C71).

test('photos and cards live on the private media disk', function () {
    expect(config('filesystems.disks.media'))->toMatchArray([
        'driver' => 'local',
        'root' => storage_path('app/media'),
        'visibility' => 'private',
    ])
        ->and(config('mandato.media_disk'))->toBe('media')
        ->and(array_values(config('filesystems.links')))->not->toContain(storage_path('app/media'));

    // storage/app ignores everything but the folders it lists (git is not reachable inside Sail)
    $rules = array_map('trim', file(storage_path('app/.gitignore')));
    expect($rules)->toContain('*')
        ->and(array_filter($rules, fn (string $r) => str_starts_with($r, '!') && str_contains($r, 'media')))->toBe([]);
});

/** @return array<string, mixed> */
function photoRow(array $change = []): array
{
    return [
        'house' => 'camara', 'member_source_id' => '101', 'sha256' => str_repeat('a', 64), 'source_url' => 'https://x', 'final_url' => 'https://x',
        'bytes' => 1, 'width' => 354, 'height' => 472, 'fetched_at' => now(), 'checked_at' => now(), ...$change,
    ];
}

/** @return array<string, mixed> */
function snapshotRow(array $change = []): array
{
    return [
        'code' => '20270301-AAAAAAAA', 'digest' => str_repeat('b', 64), 'kind' => 'member', 'house' => 'camara', 'source_id' => '101',
        'legislature' => 58, 'template' => 1, 'payload' => '{}', 'created_at' => now(), ...$change,
    ];
}

test('photo and card tables keep their constraints', function () {
    $columns = fn (string $table) => collect(DB::getSchemaBuilder()->getColumns($table))->pluck('type_name', 'name')->all();
    expect(array_keys($columns('photo_versions')))->toEqualCanonicalizing(['id', 'house', 'member_source_id', 'sha256', 'source_url', 'final_url', 'bytes', 'width', 'height', 'fetched_at', 'checked_at'])
        ->and(array_keys($columns('card_snapshots')))->toEqualCanonicalizing(['id', 'code', 'digest', 'kind', 'house', 'source_id', 'legislature', 'template', 'payload', 'created_at'])
        ->and($columns('card_snapshots')['payload'])->toBe('jsonb')
        ->and(DB::getSchemaBuilder()->getForeignKeys('photo_versions'))->toBe([])
        ->and(DB::getSchemaBuilder()->getForeignKeys('card_snapshots'))->toBe([]);

    $indexes = collect(DB::getSchemaBuilder()->getIndexes('card_snapshots'))->map(fn ($i) => [$i['columns'], $i['unique']])->all();
    expect($indexes)->toContain([['code'], true], [['digest'], true], [['kind', 'house', 'source_id', 'legislature', 'id'], false]);
    $indexes = collect(DB::getSchemaBuilder()->getIndexes('photo_versions'))->map(fn ($i) => [$i['columns'], $i['unique']])->all();
    expect($indexes)->toContain([['house', 'member_source_id', 'sha256'], true]);

    DB::table('photo_versions')->insert(photoRow());
    DB::table('card_snapshots')->insert(snapshotRow());

    expect(sqlState(fn () => DB::table('photo_versions')->insert(photoRow())))->toBe('23505')
        ->and(sqlState(fn () => DB::table('photo_versions')->insert(photoRow(['member_source_id' => '102']))))->toBeNull()
        ->and(sqlState(fn () => DB::table('photo_versions')->insert(photoRow(['house' => 'presidencia', 'sha256' => str_repeat('c', 64)]))))->toBe('23514')
        ->and(sqlState(fn () => DB::table('card_snapshots')->insert(snapshotRow(['digest' => str_repeat('c', 64)]))))->toBe('23505')
        ->and(sqlState(fn () => DB::table('card_snapshots')->insert(snapshotRow(['code' => '20270301-BBBBBBBB']))))->toBe('23505')
        ->and(sqlState(fn () => DB::table('card_snapshots')->insert(snapshotRow(['code' => '20270301-CCCCCCCC', 'digest' => str_repeat('d', 64), 'house' => 'presidencia']))))->toBe('23514')
        ->and(sqlState(fn () => DB::table('card_snapshots')->insert(snapshotRow(['code' => '20270301-DDDDDDDD', 'digest' => str_repeat('e', 64), 'kind' => 'profile']))))->toBe('23514')
        ->and(sqlState(fn () => DB::table('card_snapshots')->insert(snapshotRow(['code' => '20270301-EEEEEEEE', 'digest' => str_repeat('f', 64), 'kind' => 'roll_call', 'legislature' => null]))))->toBeNull();
});
