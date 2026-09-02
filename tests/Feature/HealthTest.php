<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('health endpoint reports application database and cache status without secrets', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'application' => 'ok',
                'database' => 'ok',
                'cache' => 'ok',
            ],
        ]);
});

test('health endpoint returns service unavailable when the database check fails', function () {
    DB::shouldReceive('selectOne')->once()->andThrow(new RuntimeException('unavailable'));

    $this->getJson('/health')
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.database', 'failed')
        ->assertJsonMissing(['message' => 'unavailable']);
});

test('health endpoint returns service unavailable when the cache check fails', function () {
    Cache::shouldReceive('put')->once()->andThrow(new RuntimeException('unavailable'));

    $this->getJson('/health')
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.cache', 'failed')
        ->assertJsonMissing(['message' => 'unavailable']);
});
