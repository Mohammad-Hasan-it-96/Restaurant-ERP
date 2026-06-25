<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * GET /api/health
     *
     * Liveness/readiness probe for monitoring. Verifies the DB connection and
     * returns 200 when healthy, 503 when the DB is unreachable so uptime tooling
     * can detect the failure. Intentionally unthrottled and session-less.
     */
    public function __invoke(): JsonResponse
    {
        $db = true;

        try {
            // Cheap round-trip that actually exercises the connection.
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            $db = false;
            Log::error('health.db_failure', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => $db ? 'ok' : 'error',
            'db' => $db,
            'time' => now()->toIso8601String(),
        ], $db ? 200 : 503);
    }
}
