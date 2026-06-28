<?php

/*
|--------------------------------------------------------------------------
| Health check
|--------------------------------------------------------------------------
|
| Tuning for the admin-gated health report (GET /api/health, served by
| App\Services\HealthService). Critical subsystems (database, cache, storage)
| failing return 503; the thresholds below decide when capacity signals
| (disk usage, failed jobs) report "degraded" (still HTTP 200). The public,
| dependency-free liveness probe for load balancers remains Laravel's /up.
|
*/

return [

    'thresholds' => [
        // Disk usage at or above this percent → the disk check reports "degraded".
        'disk_warn_percent' => (float) env('HEALTH_DISK_WARN_PERCENT', 90),

        // failed_jobs row count above this → the queue check reports "degraded".
        'failed_jobs_warn' => (int) env('HEALTH_FAILED_JOBS_WARN', 25),
    ],

    // Disk used for the storage read/write/delete round-trip probe.
    'storage_disk' => 'local',

    // Filesystem path measured for disk-usage reporting.
    'disk_usage_path' => storage_path(),

];
