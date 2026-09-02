<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'application' => 'ok',
            'database' => $this->databaseStatus(),
            'cache' => $this->cacheStatus(),
        ];
        $healthy = ! in_array('failed', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function databaseStatus(): string
    {
        try {
            DB::selectOne('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }

    private function cacheStatus(): string
    {
        try {
            $key = 'health:'.bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);
            $healthy = Cache::pull($key) === 'ok';

            return $healthy ? 'ok' : 'failed';
        } catch (Throwable) {
            return 'failed';
        }
    }
}
