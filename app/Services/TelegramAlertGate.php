<?php

namespace App\Services;

use App\Jobs\SendTelegramAlertJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\RateLimiter;

/**
 * TelegramAlertGate
 *
 * The decision engine for the Telegram alert pipeline. Given a normalised log
 * record, it runs the pipeline in order and short-circuits on any miss:
 *   1. enabled + environment check
 *   2. level floor
 *   3. category match (critical => "critical"; uncaught => "fatal";
 *      error => allowlist of event prefixes / exception classes)
 *   4. duplicate suppression (fingerprint, cache TTL)
 *   5. per-category rate limit
 *   6. bot routing (by category, '*' = all)
 *   7. delivery (sync HTTP for infra categories, queued job otherwise)
 *
 * Also callable directly (e.g. from the unhandled-exception reporter) so fatal
 * alerts do not depend on the log channel.
 */
class TelegramAlertGate
{
    public function handle(array $record): void
    {
        $cfg = config('alerts');

        if (! ($cfg['enabled'] ?? false)) {
            return;
        }

        if (! in_array(app()->environment(), $cfg['environments'] ?? [], true)) {
            return;
        }

        $levelValue = (int) ($record['level_value'] ?? 0);
        if ($levelValue < $this->levelValue($cfg['min_level'] ?? 'error')) {
            return;
        }

        $level = strtolower((string) ($record['level'] ?? ''));
        $event = (string) ($record['message'] ?? '');
        // Merge globally-injected context (request_id, route, user_id, …) so the
        // direct-call path carries it too; the Monolog path already has it.
        $context = array_merge(Context::all(), $record['context'] ?? []);

        $category = $this->matchCategory($level, $event, $context, $cfg);
        if ($category === null) {
            return;
        }

        // Duplicate suppression.
        $ttl = (int) ($cfg['dedup']['ttl'] ?? 300);
        if (! Cache::add('alert:dedup:'.$this->fingerprint($category, $event, $context), 1, $ttl)) {
            return;
        }

        // Per-category rate limit.
        $rlKey = 'alert:rate:'.$category;
        $max = (int) ($cfg['rate_limit']['max'] ?? 10);
        if (RateLimiter::tooManyAttempts($rlKey, $max)) {
            return;
        }
        RateLimiter::hit($rlKey, (int) ($cfg['rate_limit']['per_seconds'] ?? 300));

        $text = $this->formatMessage($category, $level, $event, $context);
        $sync = in_array($category, $cfg['sync_categories'] ?? [], true);

        foreach ($cfg['bots'] ?? [] as $bot) {
            if (empty($bot['token']) || empty($bot['chat_id'])) {
                continue;
            }
            $cats = $bot['categories'] ?? ['*'];
            if (! in_array('*', $cats, true) && ! in_array($category, $cats, true)) {
                continue;
            }

            if ($sync) {
                app(TelegramClient::class)->sendSync($bot, $text);
            } else {
                SendTelegramAlertJob::dispatch($bot, $text);
            }
        }
    }

    /**
     * Hybrid rule: critical level always alerts; uncaught (fatal flag) alerts;
     * error level alerts only on the configured allowlist.
     */
    private function matchCategory(string $level, string $event, array $context, array $cfg): ?string
    {
        if (! empty($context['fatal'])) {
            return 'fatal';
        }

        if ($level === 'critical') {
            return 'critical';
        }

        $excClass = $this->exceptionClass($context);

        foreach (($cfg['categories'] ?? []) as $name => $rules) {
            foreach (($rules['prefixes'] ?? []) as $prefix) {
                if (str_starts_with($event, $prefix)) {
                    return $name;
                }
            }
            if ($excClass) {
                foreach (($rules['exceptions'] ?? []) as $match) {
                    if (is_a($excClass, $match, true)) {
                        return $name;
                    }
                }
            }
        }

        return null;
    }

    private function fingerprint(string $category, string $event, array $context): string
    {
        $exc = $context['exception'] ?? null;
        $location = is_array($exc)
            ? ($exc['file'] ?? '')
            : (is_object($exc) && method_exists($exc, 'getFile') ? $exc->getFile().':'.$exc->getLine() : '');

        return sha1($category.'|'.$event.'|'.($this->exceptionClass($context) ?? '').'|'.$location);
    }

    private function formatMessage(string $category, string $level, string $event, array $context): string
    {
        $lines = [];
        $lines[] = '🚨 ['.strtoupper($category).'] '.strtoupper($level);
        $lines[] = 'env: '.app()->environment().' @ '.gethostname();
        $lines[] = 'event: '.$event;

        $exc = $context['exception'] ?? null;
        if (is_array($exc)) {
            $lines[] = 'exception: '.($exc['class'] ?? '').' — '.($exc['message'] ?? '');
            if (! empty($exc['file'])) {
                $lines[] = 'at: '.$exc['file'];
            }
        } elseif (is_object($exc) && method_exists($exc, 'getMessage')) {
            $lines[] = 'exception: '.get_class($exc).' — '.$exc->getMessage();
            $lines[] = 'at: '.$exc->getFile().':'.$exc->getLine();
        }

        foreach (['request_id', 'route', 'user_id', 'customer_id', 'order_id'] as $key) {
            if (! empty($context[$key])) {
                $lines[] = $key.': '.$context[$key];
            }
        }

        return implode("\n", $lines);
    }

    private function exceptionClass(array $context): ?string
    {
        $exc = $context['exception'] ?? null;

        if (is_array($exc)) {
            return $exc['class'] ?? null;
        }

        return is_object($exc) ? get_class($exc) : null;
    }

    private function levelValue(string $name): int
    {
        return match (strtolower($name)) {
            'debug' => 100,
            'info' => 200,
            'notice' => 250,
            'warning' => 300,
            'error' => 400,
            'critical' => 500,
            'alert' => 550,
            'emergency' => 600,
            default => 400,
        };
    }
}
