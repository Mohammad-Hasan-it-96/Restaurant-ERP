<?php

/*
|--------------------------------------------------------------------------
| Activity Log (business audit trail)
|--------------------------------------------------------------------------
|
| Settings for the curated business audit trail written via
| App\Services\ActivityLogger and browsable at /admin/activity-logs. The DB
| table grows over time, so old rows are pruned by the scheduled
| `activitylog:prune` command using the retention window below.
|
*/

return [

    // Master switch — when false, activity()->log() becomes a no-op.
    'enabled' => env('ACTIVITYLOG_ENABLED', true),

    // Entries older than this many days are removed by `activitylog:prune`.
    'retention_days' => (int) env('ACTIVITYLOG_RETENTION_DAYS', 365),

];
