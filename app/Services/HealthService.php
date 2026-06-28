<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * HealthService — the check engine behind the admin health endpoint.
 *
 * Probes each subsystem in its own try/catch (one failure never crashes the
 * report) and derives an overall status with the precedence error > degraded > ok:
 *   - critical subsystems (database, cache, storage) failing  → "error" (HTTP 503)
 *   - capacity warnings (disk usage, failed jobs over threshold) → "degraded" (200)
 *   - otherwise → "ok" (200)
 *
 * Failures are logged through LogService (health.* events).
 */
class HealthService
{
    /** Subsystems whose failure flips the whole report to error (→ 503). */
    private const CRITICAL = ['database', 'cache', 'storage'];

    public function report(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDisk(),
        ];

        return [
            'status' => $this->overallStatus($checks),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'versions' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
        ];
    }

    private function overallStatus(array $checks): string
    {
        foreach (self::CRITICAL as $key) {
            if (($checks[$key]['status'] ?? null) === 'error') {
                return 'error';
            }
        }

        foreach ($checks as $check) {
            if (in_array($check['status'] ?? null, ['degraded', 'error'], true)) {
                return 'degraded';
            }
        }

        return 'ok';
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            logService()->error('health.db_failure', [], $e);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.Str::random(8);
            Cache::put($key, '1', 10);
            $ok = Cache::get($key) === '1';
            Cache::forget($key);

            return $ok
                ? ['status' => 'ok']
                : ['status' => 'error', 'message' => 'cache round-trip mismatch'];
        } catch (\Throwable $e) {
            logService()->error('health.cache_failure', [], $e);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $disk = config('health.storage_disk', 'local');

        try {
            $file = 'health_check_'.Str::random(8).'.txt';
            Storage::disk($disk)->put($file, 'ok');
            $ok = Storage::disk($disk)->get($file) === 'ok';
            Storage::disk($disk)->delete($file);

            return $ok
                ? ['status' => 'ok', 'disk' => $disk]
                : ['status' => 'error', 'disk' => $disk, 'message' => 'storage round-trip mismatch'];
        } catch (\Throwable $e) {
            logService()->error('health.storage_failure', ['disk' => $disk], $e);

            return ['status' => 'error', 'disk' => $disk, 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $warn = (int) config('health.thresholds.failed_jobs_warn', 25);

            return [
                'status' => $failed > $warn ? 'degraded' : 'ok',
                'pending' => $pending,
                'failed' => $failed,
            ];
        } catch (\Throwable $e) {
            logService()->error('health.queue_failure', [], $e);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkDisk(): array
    {
        try {
            $path = config('health.disk_usage_path') ?: storage_path();
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);

            if (! $total || $total <= 0) {
                return ['status' => 'error', 'message' => 'unable to read disk space'];
            }

            $usedPercent = round((($total - $free) / $total) * 100, 1);
            $warn = (float) config('health.thresholds.disk_warn_percent', 90);

            return [
                'status' => $usedPercent >= $warn ? 'degraded' : 'ok',
                'used_percent' => $usedPercent,
                'free_bytes' => (int) $free,
                'total_bytes' => (int) $total,
            ];
        } catch (\Throwable $e) {
            logService()->error('health.disk_failure', [], $e);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
