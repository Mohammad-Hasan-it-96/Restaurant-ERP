<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * GET /api/health
     *
     * Comprehensive, admin-gated health report: database, cache, queue, storage,
     * disk usage, plus PHP/Laravel versions. Returns 503 when a critical subsystem
     * (database/cache/storage) is down, otherwise 200 (including "degraded").
     *
     * Note: this route is gated by auth+admin (see routes/web.php). Automated
     * external liveness should use the public, dependency-free /up route instead,
     * since a DB outage fails auth here before this controller runs.
     */
    public function __invoke(HealthService $health): JsonResponse
    {
        $report = $health->report();

        return response()->json($report, $report['status'] === 'error' ? 503 : 200);
    }
}
