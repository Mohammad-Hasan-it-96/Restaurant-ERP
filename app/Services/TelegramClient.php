<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * TelegramClient
 *
 * Thin wrapper over the Telegram Bot sendMessage API. Two entry points:
 *  - send()     : may throw; used by the queued SendTelegramAlertJob (which
 *                 retries and logs a warning on final failure).
 *  - sendSync() : swallows/logs-as-warning any failure so an infra outage
 *                 (queue/DB down) cannot cascade when alerting synchronously.
 *
 * Self-failures are logged at WARNING — below the alert floor — so a Telegram
 * outage can never generate a new alert (no loops).
 */
class TelegramClient
{
    public function send(array $bot, string $text): void
    {
        $this->post($bot, $text, 10);
    }

    public function sendSync(array $bot, string $text): void
    {
        try {
            $this->post($bot, $text, 3);
        } catch (\Throwable $e) {
            logService()->warning('telegram.alert.failed', [
                'bot' => $bot['name'] ?? '?',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function post(array $bot, string $text, int $timeout): void
    {
        $token = $bot['token'] ?? null;
        $chatId = $bot['chat_id'] ?? null;

        if (! $token || ! $chatId) {
            return;
        }

        Http::timeout($timeout)
            ->asForm()
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                // Telegram hard-caps messages at 4096 chars.
                'text' => mb_substr($text, 0, 4000),
                'disable_web_page_preview' => true,
            ])
            ->throw();
    }
}
