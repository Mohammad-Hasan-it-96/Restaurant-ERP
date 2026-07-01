<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * Removes activity-log rows older than config('activitylog.retention_days').
 * Scheduled daily (see bootstrap/app.php withSchedule).
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'activitylog:prune';

    protected $description = 'Delete activity log entries older than the configured retention period';

    public function handle(): int
    {
        $days = (int) config('activitylog.retention_days', 365);
        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} activity log entries older than {$days} days.");
        logService()->info('activitylog.pruned', ['deleted' => $deleted, 'days' => $days]);

        return self::SUCCESS;
    }
}
