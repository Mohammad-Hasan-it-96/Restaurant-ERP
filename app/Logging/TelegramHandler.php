<?php

namespace App\Logging;

use App\Services\TelegramAlertGate;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * TelegramHandler
 *
 * Thin Monolog boundary for the Telegram alert pipeline. It is wired into the
 * `app` log stack (config/logging.php) only when LOG_TELEGRAM_ENABLED=true, so
 * it stays a zero-cost seam otherwise. All decisions (category match, dedup,
 * rate limit, env-awareness, bot routing, sync-vs-queued delivery) live in
 * App\Services\TelegramAlertGate — this class just normalises the record and
 * hands it over, and NEVER throws (logging must not crash the request/worker).
 */
class TelegramHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            app(TelegramAlertGate::class)->handle([
                'level' => $record->level->getName(),
                'level_value' => $record->level->value,
                'message' => $record->message,
                'context' => $record->context,
                'datetime' => $record->datetime->format('c'),
            ]);
        } catch (\Throwable $e) {
            // Swallow — a failure to alert must never break logging.
        }
    }
}
