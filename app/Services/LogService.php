<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * LogService
 *
 * The single, central entry point for application logging. Everything that
 * needs to write a log line should go through here (controllers, services,
 * jobs, middleware) instead of calling the Log facade directly.
 *
 * Responsibilities:
 *  - Expose the four levels we standardise on: info / warning / error / critical.
 *  - Normalise a Throwable into a compact, non-sensitive context array.
 *
 * What it deliberately does NOT do:
 *  - It does not attach shared request context (request_id, user_id,
 *    customer_id, route, ip, user_agent). That is injected globally via the
 *    Context facade in App\Http\Middleware\InjectLogContext, so EVERY log line
 *    — including framework and uncaught-exception logs — carries it without
 *    being repeated at each call site.
 *
 * Event names follow the established dot convention, e.g. "order.create.failed".
 *
 * Resolve via the container or the global logService() helper:
 *   logService()->error('order.create.failed', ['order_id' => $id], $e);
 */
class LogService
{
    public function info(string $event, array $context = []): void
    {
        Log::info($event, $context);
    }

    public function warning(string $event, array $context = []): void
    {
        Log::warning($event, $context);
    }

    public function error(string $event, array $context = [], ?Throwable $e = null): void
    {
        Log::error($event, $this->withException($context, $e));
    }

    public function critical(string $event, array $context = [], ?Throwable $e = null): void
    {
        Log::critical($event, $this->withException($context, $e));
    }

    /**
     * Merge a compact, non-sensitive exception summary into the context.
     * We intentionally keep only class/message/location (no full stack trace
     * or request payload) to avoid leaking PII and bloating the log files.
     */
    private function withException(array $context, ?Throwable $e): array
    {
        if ($e) {
            $context['exception'] = [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ];
        }

        return $context;
    }
}
