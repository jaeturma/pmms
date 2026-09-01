<?php

use App\Providers\AppServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    config(['database.default' => 'sqlite']);

    foreach (['configured_default', 'fresh_install', 'unavailable'] as $connection) {
        DB::purge($connection);
    }
});

function readBootSettings(AppServiceProvider $provider): mixed
{
    $method = new ReflectionMethod($provider, 'systemSettings');

    return $method->invoke($provider);
}

test('boot settings use the configured default connection', function () {
    config([
        'database.default' => 'configured_default',
        'database.connections.configured_default' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('configured_default');
    DB::connection('configured_default')->getSchemaBuilder()->create('system_settings', function ($table): void {
        $table->id();
        $table->timestamps();
    });

    $connections = collect();
    DB::listen(function (QueryExecuted $query) use ($connections): void {
        if (str_contains($query->sql, 'system_settings')) {
            $connections->push($query->connectionName);
        }
    });

    readBootSettings(new AppServiceProvider(app()));

    expect($connections)->not->toBeEmpty()
        ->and($connections->unique()->all())->toBe(['configured_default']);
});

test('boot settings are safe before the system settings migration', function () {
    config([
        'database.default' => 'fresh_install',
        'database.connections.fresh_install' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('fresh_install');

    expect(readBootSettings(new AppServiceProvider(app())))->toBeNull();
});

test('boot settings fail open when the configured database is unavailable', function () {
    config([
        'database.default' => 'unavailable',
        'database.connections.unavailable' => [
            'driver' => 'sqlite',
            'database' => storage_path('framework/missing/database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('unavailable');

    expect(readBootSettings(new AppServiceProvider(app())))->toBeNull();
});
