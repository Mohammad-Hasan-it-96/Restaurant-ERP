<?php

/*
|--------------------------------------------------------------------------
| Telegram Alert pipeline
|--------------------------------------------------------------------------
|
| Curated, operator-facing alerting layered on top of the standard logging
| stack. The `telegram` log channel (config/logging.php) routes error+critical
| records to App\Logging\TelegramHandler, which hands each record to
| App\Services\TelegramAlertGate. The gate decides — using the rules below —
| whether the record is one of the few BUSINESS-CRITICAL categories worth
| paging on, and only then sends it to Telegram.
|
| Categories sent: critical exceptions, failed payments, queue failures,
| database failures, and fatal/uncaught errors. Routine error() logs stay
| silent. Reads via config('alerts.*'); enabling requires LOG_TELEGRAM_ENABLED
| plus TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID, then `php artisan config:cache`.
|
*/

return [

    // Master switch — shared with the logging seam so the channel and the gate agree.
    'enabled' => env('LOG_TELEGRAM_ENABLED', false),

    // Environment-aware: never alert from local/testing.
    'environments' => ['production', 'staging'],

    // Floor — records below this Monolog level are ignored (aligns with LOG_TELEGRAM_LEVEL).
    'min_level' => env('LOG_TELEGRAM_LEVEL', 'error'),

    /*
    | Category detection for ERROR-level records (CRITICAL level always alerts as
    | the "critical" category; uncaught exceptions alert as "fatal"). An error
    | record matches a category when its event name starts with one of `prefixes`
    | OR the folded exception class is (a subclass of) one of `exceptions`.
    */
    'categories' => [
        'payment' => [
            'prefixes' => ['payment.'],
        ],
        'queue' => [
            'prefixes' => ['queue.'],
        ],
        'database' => [
            'prefixes' => ['db.', 'health.db_failure'],
            'exceptions' => [
                \Illuminate\Database\QueryException::class,
                \PDOException::class,
            ],
        ],
        // Backup/cleanup failures (backup.failed / backup.cleanup_failed) log at
        // error level; without this allowlist entry they would never page.
        'backup' => [
            'prefixes' => ['backup.failed', 'backup.cleanup_failed'],
        ],
    ],

    // Categories delivered SYNCHRONOUSLY (direct HTTP) because the queue/DB may be
    // the thing that is broken, or the emitter is a CLI process (scheduler) with no
    // guaranteed worker. Everything else is dispatched via a queued job.
    'sync_categories' => ['queue', 'database', 'backup', 'fatal'],

    // Duplicate suppression: identical fingerprints within this window send once.
    'dedup' => [
        'ttl' => (int) env('ALERT_DEDUP_TTL', 300),
    ],

    // Per-category rate limit: at most `max` alerts per `per_seconds` window.
    'rate_limit' => [
        'max' => (int) env('ALERT_RATE_MAX', 10),
        'per_seconds' => (int) env('ALERT_RATE_WINDOW', 300),
    ],

    /*
    | Multiple bots, routed by category. Each bot receives a category when its
    | `categories` list contains that category or the wildcard '*'. Add more bots
    | (e.g. a payments-only bot) without touching the gate.
    */
    'bots' => [
        [
            'name' => 'default',
            'token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'categories' => ['*'],
        ],
    ],

];
